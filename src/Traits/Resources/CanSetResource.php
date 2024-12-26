<?php

namespace Wsmallnews\Support\Traits\Resources;

use Illuminate\Support\Str;

trait CanSetResource
{
    // 批量设置导航
    public static function setResources(array $resourceInfos)
    {
        foreach ($resourceInfos['resources'] as $resource => $resourceInfo) {
            $resourceInfo = array_merge($resourceInfos['group_info'], $resourceInfo);

            foreach ($resourceInfo as $attributeKey => $attributeValue) {
                $attributeKey = Str::camel($attributeKey);
                if (method_exists($resource, $attributeKey)) {
                    $resource::$attributeKey($attributeValue);
                } else {
                    $resource::setAttribute($attributeKey, $attributeValue);
                }
            }
        }
    }



    public static function setPages(array $pageInfos)
    {
        foreach ($pageInfos['pages'] as $page => $pageInfo) {
            $pageInfo = array_merge($pageInfos['group_info'], $pageInfo);
        } 

        foreach ($pageInfo as $attributeKey => $attributeValue) {
            $attributeKey = Str::camel($attributeKey);
            if (method_exists($page, $attributeKey)) {
                $page::$attributeKey($attributeValue);
            } else {
                $page::setAttribute($attributeKey, $attributeValue);
            }
        }
    }
}
