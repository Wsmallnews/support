export default function arrangeFormField({ state, arrangeToRecursionKey, tableFields }) {
    return {
        state,
        arrangeToRecursionKey,
        tableFields,
        arrangeTempIdCount: 1,
        recursionTempIdCount: 1,
        arrangeRealIdToTempId: [],
        arranges: [],
        recursions: [],
        init: function() {
            // 初始化 arranges
            this.initArranges();

            // 初始化 recursions
            this.initRecursions();

            // 如果没有 arranges ，则默认初始化一个主规格，并附带一个子规格
            if (!this.arranges) {
                this.autoFirst();
            }

            // 构建 recursion table
            this.buildRecursionsTable();
            this.updateState();

            this.$watch('arranges', (arranges, oldArranges) => {
                this.buildRecursionsTable();
                this.updateState();
            })

            // this.$watch('recursions', (recursions, oldRecursions) => {
            //     this.updateState();          // x-model 的浅绑定会自动更新 state, 这个监听先注释
            // })
        },
        initArranges: function() {
            let stateArranges = this.state.arranges ?? [];

            stateArranges.forEach((stateArrange, index) => {
                let currentArrange = stateArrange;
                currentArrange['temp_id'] = this.arrangeTempIdCount++;
                
                let currentChildren = [];
                stateArrange.children.forEach((children, idx) => {
                    // 为每个 规格项增加当前页面自增计数器，比较唯一用
                    children['temp_id'] = this.arrangeTempIdCount++;

                    // 记录规格项真实 id 对应的 临时 id
                    this.arrangeRealIdToTempId[children.id] = children['temp_id'];

                    currentChildren.push(children)
                })

                this.arranges.push(currentArrange)
            })
        },
        initRecursions: function() {
            let stateRecursions = this.state.recursions ?? [];

            stateRecursions.forEach((stateRecursion, index) => {
                let currentRecursion = stateRecursion;

                // 增加临时 id
                currentRecursion['temp_id'] = this.recursionTempIdCount++;
                
                // 将真实 id 数组，循环，找到对应的临时 id 组合成数组
                currentRecursion['arrange_temp_ids'] = [];
                let arrangeToRecursionIds = currentRecursion[this.arrangeToRecursionKey].split(',');
                arrangeToRecursionIds.forEach((ids) => {
                    if (this.arrangeRealIdToTempId[ids]) {
                        currentRecursion['arrange_temp_ids'].push(this.arrangeRealIdToTempId[ids]);
                    }
                })
                
                if (arrangeToRecursionIds.length == currentRecursion['arrange_temp_ids'].length) {
                    // 能找到匹配的 arranges ，找不到的丢弃
                    this.recursions.push(currentRecursion)
                }
            })
        },
        updateState: function () {
            let state = {}

            state['arranges'] = this.arranges;
            state['recursions'] = this.recursions;

            this.state = state
        },
        arrangeTemplate: function () {
            return {
                id: 0,
                temp_id: this.arrangeTempIdCount++,
                name: "",
                order_column: this.arranges.length,
                children: []
            };
        },
        childrenArrangeTemplate: function (index) {
            return {
                id: 0,
                temp_id: this.arrangeTempIdCount++,
                name: "",
                image: "",
                order_column: this.arranges[index].children.length
            }
        },
        // 自动初始化 arranges
        autoFirst: function () {
            this.arranges = [this.arrangeTemplate()]
            this.arranges[0].children.push(this.childrenArrangeTemplate(0))
        },
        // 添加主规格
        addArrange: function () {
            this.arranges.push(this.arrangeTemplate());
        },
        // 添加子规格
        addChildrenArrange: function(index) {
            this.arranges[index].children.push(this.childrenArrangeTemplate(index));

            if (this.arranges[index].children.length == 1) {        // 新加的主规格包含了一个子规格，主规格生效
                this.recursions = []; // 规格大变化，清空 recursions
            }
        },
        deleteArrange: function (index) {
            // 如果删除的规格包含子规格
            if (this.arranges[index].children.length) {
                this.recursions = []; // 规格大变化，清空 recursions
            }

            this.arranges.splice(index, 1);
        },
        // 删除子规格
        deleteChildrenArrange: function (parentIndex, index) {
            let data = this.arranges[parentIndex].children[index];
            
            // 直接将子规格删除
            this.arranges[parentIndex].children.splice(index, 1);

            if (this.arranges[parentIndex].children.length <= 0) {
                // 当前规格项，所有子规格都被删除，清空 recursions
                this.recursions = [];        // 规格大变化，清空 recursions
            } else {
                // 查询 recursions 中包含被删除的的子规格的项，然后移除
                let deleteRecursionIndexArr = [];
                this.recursions.forEach((recursion, index) => {
                    recursion.arrange_texts.forEach((arrange_text, ix) => {
                        if (arrange_text == data.name) {
                            deleteRecursionIndexArr.push(index);
                        }
                    });
                });
                deleteRecursionIndexArr.sort(function (a, b) {
                    return b - a;
                });
                // 移除有相关子规格的项
                deleteRecursionIndexArr.forEach((recursionIndex, index) => {
                    // recursionIndex 为要删除的 recursions 的 index
                    this.recursions.splice(recursionIndex, 1);
                });
            }
        },
        // 重新构建 recursions 表格
        buildRecursionsTable: function () {
            let arrangeChildrenIdArr = [];

            this.arranges.forEach((arrange, key) => {
                let children = arrange.children;
                let childrenIdArr = [];
                
                if (children.length > 0) {
                    children.forEach((child, k) => {
                        childrenIdArr.push(child.temp_id);
                    });

                    arrangeChildrenIdArr.push(childrenIdArr)
                }
            });

            this.recursionFunc(arrangeChildrenIdArr);
        },
        // 递归
        recursionFunc: function (arrangeChildrenIdArr, arrangeK = 0, temp = []) {
            if (arrangeK == arrangeChildrenIdArr.length && arrangeK != 0) {
                let tempDetail = [];
                let tempDetailIds = [];

                temp.forEach((item, index) => {
                    this.arranges.forEach((arrange, inx) => {
                        arrange.children.forEach((child, ix) => {
                            if (item == child.temp_id) {
                                tempDetail.push(child.name);
                                tempDetailIds.push(child.temp_id);
                            }
                        })
                    })
                })

                let flag = false; // 默认添加新的
                for (let i in this.recursions) {
                    // 保证数组是同一个顺序
                    this.recursions[i].arrange_temp_ids.sort();
                    tempDetailIds.sort();

                    if (this.recursions[i].arrange_temp_ids.join(',') == tempDetailIds.join(',')) {
                        flag = i;
                        break;
                    }
                }

                if (!flag) {
                    let pushRecursion = {
                        id: 0,
                        temp_id: this.recursionTempIdCount + 1,
                        arrange_texts: tempDetail,
                        arrange_temp_ids: tempDetailIds,
                    };

                    // 将 recursion 的自定义字段初始化
                    this.tableFields.forEach((field) => {
                        pushRecursion[field.field] = field.default
                    })

                    // 初始化转换字段
                    pushRecursion[this.arrangeToRecursionKey] = '';

                    this.recursions.push(pushRecursion)
                } else {
                    this.recursions[flag].arrange_texts = tempDetail;
                    this.recursions[flag].arrange_temp_ids = tempDetailIds;
                }
            }

            if (arrangeChildrenIdArr.length) {
                arrangeChildrenIdArr[arrangeK] && arrangeChildrenIdArr[arrangeK].forEach((cv, ck) => {
                    temp[arrangeK] = arrangeChildrenIdArr[arrangeK][ck];

                    this.recursionFunc(arrangeChildrenIdArr, arrangeK + 1, temp);
                })
            }
        }
    }
}
