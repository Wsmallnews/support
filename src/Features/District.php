<?php

namespace Wsmallnews\Support\Features;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Wsmallnews\Support\Exceptions\SupportException;

class District
{
    protected $key = 'ODBBZ-BZN6Z-CCUXX-7736U-HVLST-N4FJQ';

    protected $url = 'https://apis.map.qq.com/ws/district/v1/list';

    protected $province = [];

    protected $city = [];

    protected $district = [];

    protected $cascader = [];

    protected $tilearea = [];

    protected $region = [];

    /**
     * 获取省份列表
     */
    public function getProvince()
    {
        $province = [];

        if (Storage::disk('local')->exists('district/province.txt')) {
            $province = Storage::disk('local')->get('district/province.txt');
        } else {
            if ($this->update()) {
                $province = $this->province;
            }
        }

        return $province;
    }

    /**
     * 获取城市列表
     */
    public function getCity($cidx = [])
    {
        $city = [];

        if (Storage::disk('local')->exists('district/city.txt')) {
            $city = Storage::disk('local')->get('district/city.txt');
        } else {
            if ($this->update()) {
                $city = $this->city;
            }
        }

        if (! empty($city) && ! empty($cidx)) {
            $city_tmp = [];

            for ($i = $cidx[0]; $i <= $cidx[count($cidx) - 1]; $i++) {
                $city_tmp[] = $city[$i];
            }

            $city = $city_tmp;
        }

        return $city;
    }

    /**
     * 获取地区列表
     */
    public function getDistrict($cidx = [])
    {
        $district = [];

        if (Storage::disk('local')->exists('district/district.txt')) {
            $district = Storage::disk('local')->get('district/district.txt');
        } else {
            if ($this->update()) {
                $district = $this->district;
            }
        }

        if (! empty($district) && ! empty($cidx)) {
            $district_tmp = [];

            for ($i = $cidx[0]; $i <= $cidx[count($cidx) - 1]; $i++) {
                $district_tmp[] = $district[$i];
            }

            $district = $district_tmp;
        }

        return $district;
    }

    /**
     * 获取 cascader 级联列表
     *
     * @return array
     */
    public function getCascader()
    {
        $cascader = [];

        if (Storage::disk('local')->exists('district/cascader.txt')) {
            $cascader = Storage::disk('local')->get('district/cascader.txt');
        } else {
            if ($this->update()) {
                $cascader = $this->cascader;
            }
        }

        return $cascader;
    }

    /**
     * 获取 用户端 省市区三级联动
     */
    // public function getRegion() {
    //     $region = [];

    //     if ( $this->fileExists(self::REGION_TXT) ) {
    //         $region = $this->getFile(self::REGION_TXT);
    //     } else {
    //         if ($this->setDistrict(true)) {
    //             $region = $this->region;
    //         }
    //     }

    //     return $region;
    // }

    /**
     * 获取 Tile 数据
     *
     * @return array
     */
    public function getTileArea()
    {
        $tilearea = [];

        if (Storage::disk('local')->exists('district/tilearea.txt')) {
            $tilearea = Storage::disk('local')->get('district/tilearea.txt');
        } else {
            if ($this->update()) {
                $tilearea = $this->tilearea;
            }
        }

        return $tilearea;
    }

    /**
     * 更新行政区划
     */
    public function update()
    {
        $result = $this->request();

        if ($result['status'] == 0) {
            $area = $result['result'];
            $this->province = $area[0];
            $this->city = $area[1];
            $this->district = $area[2];

            Storage::disk('local')->put('district/area.txt', json_encode($area, JSON_UNESCAPED_UNICODE));
            Storage::disk('local')->put('district/province.txt', json_encode($this->province, JSON_UNESCAPED_UNICODE));
            Storage::disk('local')->put('district/city.txt', json_encode($this->city, JSON_UNESCAPED_UNICODE));
            Storage::disk('local')->put('district/district.txt', json_encode($this->district, JSON_UNESCAPED_UNICODE));

            // cascader 格式数据
            $this->cascader = $this->setCascader();
            Storage::disk('local')->put('district/cascader.txt', json_encode($this->cascader, JSON_UNESCAPED_UNICODE));

            // 省市区数据放到一个数组平铺，并且 id 作为 key
            $this->tilearea = $this->setTileArea();
            Storage::disk('local')->put('district/tilearea.txt', json_encode($this->tilearea, JSON_UNESCAPED_UNICODE));

            return true;
        } else {
            throw new SupportException('同步行政区划失败');
        }
    }

    /**
     * 设置后台 elemetn cascader 数据格式
     *
     * @return array
     */
    protected function setCascader()
    {
        $cascaders = [];
        foreach ($this->province as $key => $value) {
            $tmp_arr = $this->getCascaderFormat($value, 'province');

            // 如果这个省下面存在城市
            if (isset($value['cidx']) && ! empty($value['cidx'])) {
                $cidx = $value['cidx'];

                // 将城市放入 citys
                $citys = [];
                for ($i = $cidx[0]; $i <= $cidx[count($cidx) - 1]; $i++) {
                    // 将城市放入 省的 children
                    $citys[] = $this->city[$i];
                }

                // 如果城市存在，循环省下面的城市
                if (isset($citys) && ! empty($citys)) {
                    $tmp_citys = [];
                    foreach ($citys as $k => $v) {
                        $tmp_city = $this->getCascaderFormat($v, 'city', $value['id']);

                        if (isset($v['cidx']) && ! empty($v['cidx'])) {
                            $cx = $v['cidx'];

                            // 将地区放入 tmp_district
                            $tmp_district = [];
                            for ($ia = $cx[0]; $ia <= $cx[count($cx) - 1]; $ia++) {
                                // 将地区放入 城市的 children
                                $tmp_district[] = $this->getCascaderFormat($this->district[$ia], 'district', $v['id']);
                            }

                            $tmp_city['children'] = $tmp_district;
                        }

                        $tmp_citys[] = $tmp_city;
                    }
                    $tmp_arr['children'] = $tmp_citys;
                }
            }

            $cascaders[] = $tmp_arr;
        }

        // 处理直辖市，上面直辖市是 二级的，将直辖市也处理成三级
        foreach ($cascaders as &$cascader) {
            // 是直辖市，处理一下
            if (in_array($cascader['id'], $this->directCityIds())) {
                // 拿到所有子集
                $currentAreas = $cascader['children'];

                // 原本 children 的第一个就是市辖区
                $currentCity = $currentAreas[0];

                // 移除第一个,获取真正的所有区
                unset($currentAreas[0]);
                $currentAreas = array_values($currentAreas);

                // 处理真正的区的 parent_id 改为 city 的 id
                foreach ($currentAreas as &$currentArea) {
                    $currentArea['parent_id'] = $currentCity['id'];
                    $currentArea['level'] = 'district';
                }

                // 将区放到市下面
                $currentCity['children'] = $currentAreas;

                // 更新省的 children
                $cascader['children'] = [$currentCity];
            }
        }

        return $cascaders;
    }

    /**
     * 格式化 cascader 数据
     *
     * @param  array  $address
     * @param  string  $level
     * @param  int  $parent_id
     * @return array
     */
    protected function getCascaderFormat($address, $level = 'province', $parent_id = 0)
    {
        $tmp_arr['name'] = $address['fullname'];
        $tmp_arr['short_name'] = isset($address['name']) ? $address['name'] : $address['fullname'];
        $tmp_arr['id'] = (string) $address['id'];
        $tmp_arr['parent_id'] = (string) $parent_id;
        $tmp_arr['level'] = $level;
        $tmp_arr['children'] = [];

        return $tmp_arr;
    }

    /**
     * 将省市区放到一个数组中，然后key 就是 id（区划代码）
     *
     * @return void
     */
    protected function setTileArea()
    {
        foreach ($this->cascader as $province) {
            $tileArea[$province['id']] = $province;
            unset($tileArea[$province['id']]['children']);              // 删除里面的 children，否则数据超级多

            foreach ($province['children'] as $city) {
                $tileArea[$city['id']] = $city;
                unset($tileArea[$city['id']]['children']);              // 删除里面的 children，否则数据超级多

                foreach ($city['children'] as $district) {
                    $tileArea[$district['id']] = $district;
                    unset($tileArea[$district['id']]['children']);      // 删除里面的 children，否则数据超级多
                }
            }
        }

        return $tileArea;
    }

    /**
     * 直辖市城市 ids
     *
     * @return array
     */
    protected function directCityIds()
    {
        $directCode = [
            '110000',
            '120000',
            '310000',
            '500000',
            '810000',
            '820000',
        ];

        return $directCode;
    }

    /**
     * 一般 微信接口请求
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $data
     * @return array
     */
    protected function request()
    {
        $client = new Client;

        $response = $client->request('get', $this->url, [
            'query' => ['key' => $this->key],
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);

        return $result;
    }
}
