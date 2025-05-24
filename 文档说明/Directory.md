# 主目录说明

``` 
bash
├── app/                  # [核心] 应用代码目录
├── config/               # [核心] 系统配置目录
├── extend/               # [扩展] 自定义类库
├── public/               # [Web入口] 对外公开目录
├── route/                # [运行时] 路由
├── runtime/              # [运行时] 缓存/日志等
├── vendor/               # [依赖] Composer 包目录
├── .env                  # 环境配置文件
├── .example.env          # 配置文件示例
├── .gitignore       	  # git忽略设置文件
├── .travis.yml      	  # yml文件
├── LICENSE.txt           # 声明
├── README.md      		  # 说明文件
├── composer.json         # composer配置文件
├── composer.lock         # composer配置文件
└── think       		  # thinkphp命令执行文件
```

## 1.  app        [核心] 应用代码目录
``` 
app\ 
├── AppFactory/           # [核心]系统工厂包
├── api/                  # 对外开放API入口
├── command/              # 自定义命令
├── http/              	  # [未启用]原定对外API入口
├── index/                # 首页目录
├── lang/                 # 公共语言包
├── machine/              # 与终端通讯
├── management/           # 管理后台业务功能
├── mobile/               # 手机移动端目录
├── pay/                  # 支付处理目录
├── wx/                   # 微信业务
├── .htaccess             # 伪静态
├── AppService.php        # 应用服务类
├── BaseController.php    # 基础控制器
├── common.php            # 公用方法
├── event.php             # 事件
├── ExceptionHandle.php   # 异常处理
├── middleware.php        # 中间件
├── provider.php          # 容器定义文件
├── Request.php           # 应用请求对象类
└── service.php           # 系统服务定义文件
```

### 1.1. AppFactory		[核心]系统工厂包，连接数据库，提供接口，处理相关业务
``` 
AppFactory/ 
├── Api/           				# 对外API应用业务
├── Kernel/                 	# 工厂核心目录
├── Machine/                 	# 终端业务
├── Management/                 # 管理后台业务
├── Mobile/                 	# 手机端H5页面业务
├── Notice/                 	# 通知业务
├── Pay/                 		# 支付业务
├── RabbitMq/                 	# RabbitMq处理业务
├── TimeTask/ 					# 定时任务
├── Wx/                 		# 微信业务
└── AppFactory.php              # 工厂包入口
```

#### 1.1.1. Api			对外开放API入口
``` 
Api/ 
├── Send/			  			# 推送外部平台目录
├── V2/			  	  			# 接收外部请求目录 
├── ApiBaseClient.php			# API业务公用处理
└── Application.php			  	# API应用业务入口
```

##### 1.1.1.1. Send			推送外部平台目录
``` 
Send/ 
└── CallbackClient.php			# 推送外部平台数据
```

##### 1.1.1.2. V2			接收外部请求目录
``` 
V2/ 
├── V2BaseClient.php			# V2公用处理
└── V2Client.php				# V2业务处理 
```

#### 1.1.2. Kernel			工厂核心目录
``` 
Kernel/ 
├── Exceptions/                 	# 异常处理目录
├── Middleware/                 	# [未启用]中间件
├── Model/                 			# 数据库模型
├── Providers/                 		# 容器Provider
├── Support/                 		# [支持]插件、特殊处理类等
├── Traits/                 		# [公共引用]代码复用方法
├── Util/                 			# 静态类
├── BaseClient.php                	# 工厂包核心处理类
├── Container.php                 	# 基础容器类
├── ServiceContainer.php          	# 容器类
└── ServiceProviderInterface.php  	# Provider接口类
```

##### 1.1.2.1. Exceptions			异常处理目录
``` 
Exceptions/ 
├── BaseException.php           				# 基础异常
├── ExcelException.php           				# Excel异常
├── ExpectedInvokableException.php 				# 预期可调用异常
├── FrozenServiceException.php      			# 冻结服务异常
├── InvalidServiceIdentifierException.php       # 无效服务标示异常
├── NoticeException.php      					# 通知异常
├── UnknownIdentifierException.php      		# 未知标识异常
└── ValidateException.php      					# 验证器异常
```

##### 1.1.2.2. Middleware			[未启用]中间件
``` 
Middleware/ 
├── Http/ 
├── Management/ 
├── Mobile/ 
└── MiddlewareInterface.php
```

##### 1.1.2.3. Model			数据库模型
``` 
Model/ 
├── Action/ 						# 操作相关
├── Activity/ 						# 营销活动
├── Advertisement/ 					# 广告
├── Api/                 			# 对外API
├── Auth/                 			# 权限控制
├── Common/                 		# 公用
├── Config/                 		# 配置
├── Earth/                 			# 地球
├── Email/                 			# 邮件
├── Export/                 		# 导出
├── Goods/                 			# 商品
├── Machine/                 		# 设备
├── MicroMall/                 		# 微商城
├── Resource/                 		# 素材库
├── Robot/                 			# 机器人
├── SaleOrders/                 	# 订单
├── Strategy/                 		# 策略
├── Suggest/                 		# 意见与建议
├── Template/                 		# 模板
├── Trip/                 			# 丽呈/携程
├── UpdateLog/                 		# 更新日志
├── User/                 			# 用户
├── Wx/                 			# 微信
└── BaseModel.php                 	# 基础公用模型
```

###### 1.1.2.3.1. Action			操作相关
``` 
Action/ 
└── ActionVideoModel.php		# 操作视频
```

###### 1.1.2.3.2. Activity			营销活动
``` 
Activity/ 
├── Coupon/ 						# 优惠券
├── Fd/ 							# 满减活动
├── Lottery/ 						# 付费抽奖
├── Pick/ 							# 提货码
├── ActivityGoodsModel.php			# 活动适用商品
└── ActivityMachineModel.php		# 活动适用设备
```

-  1.1.2.3.2.1. Coupon				优惠券
``` 
Coupon/ 
├── ActivityCouponModel.php			# 优惠券活动
└── ActivityCouponUsedModel.php		# 优惠券使用记录
```

-  1.1.2.3.2.2. Fd					满减活动
``` 
Fd/ 
├── ActivityFdContentModel.php			# 满减活动规则内容
├── ActivityFdModel.php					# 满减活动
└── ActivityFdUsedModel.php				# 满减活动使用记录
```

-  1.1.2.3.2.3. Lottery				付费抽奖
``` 
Lottery/ 
├── ActivityLotteryConfigModel.php			# 活动配置
├── ActivityLotteryContentModel.php			# 活动内容
├── ActivityLotteryModel.php				# 付费抽奖活动
├── ActivityLotteryUsedGoodsModel.php		# 中奖商品
└── ActivityLotteryUsedModel.php			# 付费抽奖活动记录
```

-  1.1.2.3.2.4. Pick				提货码
``` 
Pick/ 
├── ActivityPickCodeModel.php				# 活动码
└── ActivityPickModel.php					# 提货码活动
```

###### 1.1.2.3.3. Advertisement				广告
``` 
Advertisement/ 
├── AdvertisementPushModel.php			# 广告推送
└── AdvertisementRecordModel.php		# 广告播放记录
```

###### 1.1.2.3.4. Api					对外API接口记录
``` 
Api/ 
├── ApiAdvanceModel.php				# 外部预订订单记录
└── ApiCallbackModel.php			# 推送外部接口记录
```

###### 1.1.2.3.5. Auth					权限管理
``` 
Auth/ 
├── AuthManagerLogModel.php			# 账号操作日志
├── AuthManagerMachineModel.php		# 账号绑定设备
├── AuthManagerModel.php			# 账号
├── AuthManagerRoleModel.php		# 账号绑定权限角色
├── AuthNodeModel.php				# 权限菜单
├── AuthOrganizationModel.php		# 组织架构
├── AuthOrganizationRoleModel.php	# 组织架构绑定权限角色
├── AuthRoleModel.php				# 权限角色
└── AuthRoleNodeModel.php			# 权限角色绑定权限菜单
```

###### 1.1.2.3.6. Common			公用
``` 
Common/ 
└── CityModel.php			# 国内省市区信息表
```

###### 1.1.2.3.7. Config			系统配置
``` 
Config/ 
├── ConfigApiModel.php				# 对外API账号配置
├── ConfigLangModel.php				# 多语言配置
├── ConfigModel.php					# 系统配置
├── ConfigPerformanceModel.php		# 多语言性能参数
├── ConfigSceneLangModel.php		# 多语言场景
├── ConfigSceneModel.php			# 场景
└── ConfigSizeModel.php				# 尺寸管理
```

###### 1.1.2.3.8. Earth				地球数据
``` 
Earth/ 
├── EarthAreaModel.php			# 国内大区
├── EarthCitiesModel.php		    # 城市数据
├── EarthContinentsModel.php	    # 全球大区
├── EarthCountriesModel.php		# 全球国家
├── EarthRegionsModel.php		# 城市地区表
├── EarthStatesModel.php		    # 全球州省
└── EarthTimezoneModel.php		# 时区
```

###### 1.1.2.3.9. Email				邮件管理
``` 
Email/ 
├── EmailConfigModel.php			# 邮件配置
├── EmailTemplateLogModel.php		# 邮件通知日志
└── EmailTemplateModel.php			# 邮件模板
```

###### 1.1.2.3.10. Export			导出
``` 
Export/ 
└── ExportLogModel.php			# 导出日志
```

###### 1.1.2.3.11. Goods			商品管理
``` 
Goods/ 
├── GoodsCategoryLangModel.php			# 商品分类多语言商品分类多语言商品分类多语言商品分类多语言
├── GoodsCategoryModel.php				# 商品分类商品分类商品分类商品分类
├── GoodsChangeModel.php				    # 商品变化事件记录表  # 商品变化事件记录表  # 商品变化事件记录表  # 商品变化事件记录表
├── GoodsCornerModel.php				    # 商品角标表  # 商品角标表  # 商品角标表  # 商品角标表
├── GoodsHitModel.php					# 商品设备点击数表商品设备点击数表商品设备点击数表商品设备点击数表
├── GoodsLangModel.php					# 商品多语言表商品多语言表商品多语言表商品多语言表
├── GoodsModel.php						# 商品表商品表商品表商品表
├── GoodsMultipleGoodsModel.php			# 组合商品表组合商品表组合商品表组合商品表
├── GoodsMultipleMachineModel.php		# 组合商品详情表组合商品详情表组合商品详情表组合商品详情表
└── GoodsMultipleModel.php				# 组合商品设备表组合商品设备表组合商品设备表组合商品设备表
```

###### 1.1.2.3.12. Machine			设备管理
``` 
Machine/ 
├── MachineChannelModel.php					# 设备货道表
├── MachineChannelReplenishmentModel.php	# 货道补货记录表
├── MachineChannelStockModel.php			# 库存报表
├── MachineChannelStockReportView.php		# 库存报表视图
├── MachineCheckStockCountView.php			# 设备库存盘点视图
├── MachineCheckStockModel.php				# 设备库存盘点记录表
├── MachineConfigLangModel.php				# 设备配置多语言表
├── MachineConfigModel.php					# 设备配置表
├── MachineErrorCodeModel.php				# 设备错误码记录表
├── MachineErrorCodeSolutionModel.php		# 错误码解决方案表
├── MachineGoodsModel.php					# 设备商品表
├── MachineGroupLangModel.php				# 设备分组多语言表
├── MachineGroupMgModel.php					# 设备与分组多对多关联表
├── MachineGroupModel.php					# 设备分组表
├── MachineHelpModel.php					# 设备帮助内容信息表
├── MachineInfoModel.php					# 设备信息表
├── MachineLangModel.php					# 设备多语言表
├── MachineModel.php						# 设备主表
├── MachineMqRecordModel.php				# 设备通讯记录表
├── MachineOnOffModel.php					# 设备营业配置表
├── MachineOnlineDetailsModel.php			# 终端在线时长详情表
├── MachineOnlineModel.php					# 设备终端在线日志主表
├── MachineVersionModel.php					# 设备软件表
├── MachineVersionPlanModel.php				# 设备更新软件版本计划表
└── MachineViewModel.php					# 设备模板
```

###### 1.1.2.3.13. MicroMall			微商城
``` 
MicroMall/ 
├── MicroMallMachineModel.php			# 微商城绑定设备
└── MicroMallModel.php					# 微商城表
```

###### 1.1.2.3.14. Resource			素材库
``` 
Resource/ 
└── ResourceModel.php			# 素材表
```

###### 1.1.2.3.15. Robot			机器人
``` 
Robot/ 
└── RobotPositionModel.php			# 机器人泊车位置记录表
```

###### 1.1.2.3.16. SaleOrders			订单
``` 
SaleOrders/ 
├── SaleHotelModel.php					# 酒店订单表
├── SaleHotelNightlyModel.php			# 每日价格表
├── SaleOrdersDailyCountView.php		# 订单日统计视图
├── SaleOrdersDetailsModel.php			# 订单详情表
├── SaleOrdersGoodsCountView.php		# 订单商品日统计视图
├── SaleOrdersMachineCountView.php		# 订单设备日统计视图
├── SaleOrdersModel.php					# 销售订单列表
├── SaleOrdersRefundModel.php			# 销售订单退款列表
├── SaleOrdersRevenueModel.php			# 销售订单分账表
└── SaleOrdersUnclaimedModel.php		# 订单未取商品事件表
```

###### 1.1.2.3.17. Strategy				策略
``` 
Strategy/ 
├── StrategyIncomeModel.php			# 分润策略表
├── StrategyMachineModel.php		# 策略绑定设备表
├── StrategyManagerModel.php		# 策略绑定账号表
└── StrategyPayeeModel.php			# 收款方策略表
```

###### 1.1.2.3.18. Suggest			建议与意见
``` 
Suggest/ 
└── SuggestModel.php			# 意见与建议表
```

###### 1.1.2.3.19. Template		模板
``` 
Template/ 
├── TemplateLayoutModel.php			# 模板布局表
├── TemplateModel.php				# 模板表
├── TemplatePluginsModel.php		# 模板插件表
└── TemplateViewModel.php			# 模板视图表
```

###### 1.1.2.3.20. Trip			携程数据
``` 
Trip/ 
├── TripCityModel.php				# 携程城市表
├── TripMultipleGoodsModel.php		# 携程套餐商品表
├── TripMultipleHotelModel.php		# 携程酒店套餐商品酒店表
├── TripMultipleMachineModel.php	# 携程组合商品设备表
└── TripMultipleModel.php			# 携程套餐商品表
```

###### 1.1.2.3.21. UpdateLog		更新日志
``` 
UpdateLog/ 
└── UpdateLogModel.php			# 更新日志表
```

###### 1.1.2.3.22. User				用户
``` 
User/ 
└── UserModel.php				# 用户表
```

###### 1.1.2.3.23. Wx			微信
``` 
Wx/ 
├── WxOfficialLoginModel.php		# 微信扫码登录记录表
├── WxOfficialModel.php				# 公众号信息表
├── WxTemplateLogModel.php			# 公众号消息模板通知历史记录表
└── WxTemplateModel.php				# 公众号消息模板表
```

##### 1.1.2.4. Providers		容器provider
``` 
Providers/ 
├── Api/ 				# 对外API
├── GatewayWorker/ 		# [未使用]Socket通讯
├── Machine/ 			# 设备通讯
├── Management/			# 管理后台 
├── Mobile/ 			# 手机端H5
├── Notice/ 			# 通知
├── Pay/ 				# 支付
└── TimeTask/ 			# 定时任务
```

###### 1.1.2.4.1. Api		对外API
``` 
Api/ 
├── SendProvider.php		# 发送
└── V2Provider.php			# 接收-V2
```

###### 1.1.2.4.2. GatewayWorker			[未使用]Socket通讯
``` 
GatewayWorker/ 
├── ReceiveProvider.php			# 接收
└── SendProvider.php			# 发送
```

###### 1.1.2.4.3. Machine			设备通讯
``` 
Machine/ 
├── ReceiveProvider.php			# 接收终端
└── SendProvider.php			# 下发终端
```

###### 1.1.2.4.4. Management		管理后台
``` 
Management/ 
├── ActionProvider.php			    # 操作相关
├── ActivityProvider.php             # 营销活动
├── AdvertisementProvider.php        # 广告
├── AuthProvider.php                 # 权限
├── CommonProvider.php               # 公用
├── ConfigProvider.php               # 配置
├── EarthProvider.php                # 地球
├── EmailProvider.php                # 邮件
├── ExportProvider.php               # 导出
├── GoodsProvider.php                # 商品
├── HotelProvider.php                # 酒店
├── IndexProvider.php                # 首页
├── LoginProvider.php                # 登录
├── MachineProvider.php              # 设备
├── MicroMallProvider.php            # 微商城
├── ResourceProvider.php             # 素材库
├── SaleOrdersProvider.php           # 销售订单
├── StrategyProvider.php             # 策略管理
├── SuggestProvider.php              # 意见与建议
├── TemplateProvider.php             # 设备模板
├── TripProvider.php                 # 携程商品
├── UpdateLogProvider.php            # 更新日志
└── WxProvider.php                   # 微信
```

###### 1.1.2.4.5. Mobile        手机端H5
``` 
Mobile/ 
└── MachineProvider.php         设备数据
```

###### 1.1.2.4.6. Notice        通知
``` 
Notice/ 
├── EmailProvider.php           # 邮件通知
└── WeChatProvider.php          # 微信通知
```

###### 1.1.2.4.7. Pay              支付
``` 
Pay/ 
├── AliProvider.php             # 支付宝支付
├── JdCashierProvider.php       # 京东收银
├── SaleOrdersProvider.php      # 订单
└── WxProvider.php              # 微信支付
```

###### 1.1.2.4.8. TimeTask          定时任务
``` 
TimeTask/ 
├── ActivityProvider.php            # 营销活动
├── AuthManagerProvider.php         # 账号权限
├── ExportProvider.php              # 导出
├── GoodsProvider.php               # 商品更新
├── MachineProvider.php             # 设备
└── PaymentProvider.php             # 支付
```

##### 1.1.2.5. Support          支持类
``` 
Support/ 
├── SendNotice/                     # 发送通知
├── Trip/                           # 携程
├── Validate/                       # 验证器
├── ZdSimService/                   # 中点物联网卡
├── AuthCode.php                    # 付款码类
├── Excel.php                       # Excel导入导出
├── FileDownload.php                # 文件下载
├── Mqtt.php                        # [未使用]MQTT
├── Qr.php                          # 二维码
├── TDESUtil.php                    # 加解密
├── TencentCloud.php                # 腾讯云
└── Tree.php                        # 整理树型数据
```

###### 1.1.2.5.1. SendNotice        发送通知
``` 
SendNotice/ 
├── Email.php               # 邮件通知
└── WxMsgTemplate.php       # [未使用]微信消息模板
```

###### 1.1.2.5.2. Trip          携程
``` 
Trip/ 
├── Common.php              # 公用类，鉴权加签等
├── Hotel.php               # 携程酒店接口类
├── Order.php               # 携程订单接口类
└── Trip.php                # 携程API接口
```

###### 1.1.2.5.3. Validate      验证器
``` 
Validate/ 
├── Api/                       # 对外API验证
├── Machine/                   # 设备验证
├── Notice/                    # 通知验证
├── Pay/                       # 支付验证
├── SaleOrders/                # 订单验证
└── SupportValidate.php        # 公用    
```

-  1.1.2.5.3.1. Api         对外API验证
``` 
Api/ 
└── VV2.php             # V2类验证器
```

-  1.1.2.5.3.2. Machine     设备验证
``` 
Machine/ 
├── VChannel.php                    # 货道验证
├── VChannelReplenishment.php       # 货道补货验证
├── VMachineGoods.php               # 设备商品验证
└── VReport.php                     # 终端上报验证
```

-  1.1.2.5.3.3. Notice      通知验证
``` 
Notice/ 
└── VNotice.php     # 通知验证器
```

-  1.1.2.5.3.4. Pay         支付验证
``` 
Pay/ 
├── VAliPay.php                 # 支付宝验证
├── VJdCashierPay.php           # 京东收银验证
├── VTrip.php                   # 携程验证
└── VWxPay.php                  # 微信验证
```

-  1.1.2.5.3.5. SaleOrders          订单
``` 
SaleOrders/ 
└── VSaleOrdersRefund.php           # 订单退款验证
```

###### 1.1.2.5.4. ZdSimService      中点物联网
``` 
ZdSimService/ 
└── ZdSim.php           # 中点物联网卡类
```

##### 1.1.2.6. Traits           复用类库
``` 
Traits/ 
├── Action/                     # 操作相关
├── Activity/                   # 营销活动
├── Advertisement/              # 广告
├── Ali/                        # [废弃]支付宝
├── Api/                        # 对外API
├── Auth/                       # 账号权限
├── Config/                     # 配置
├── Earth/                      # 地球
├── Email/                      # 邮件
├── Export/                     # 导出
├── GatewayWorker/              # Socket
├── Goods/                      # 商品
├── Machine/                    # 设备
├── MicroMall/                  # 微商城
├── Mq/                         # MQ
├── Payment/                    # 支付
├── Resource/                   # 素材库
├── Robot/                      # 机器人
├── SaleOrders/                 # 销售订单
├── Send/                       # 消息发送
├── Strategy/                   # 策略
├── Suggest/                    # 建议与意见
├── Template/                   # 模板
├── Trip/                       # 丽呈小程序
├── UpdateLog/                  # 更新日志
├── User/                       # 用户
├── Wx/                         # 微信
├── CacheTrait.php              # 缓存复用类
├── CityTrait.php               # 城市复用类
├── CommonTrait.php             # 公共复用类
├── CurlTrait.php               # Curl请求
├── DbTrait.php                 # 数据库Db复用类
├── ManagementTrait.php         # 管理后台公用类
├── ModelTrait.php              # 数据库模式复用类
└── ReturnTrait.php             # 返回数据复用类
```

###### 1.1.2.6.1. Action            操作相关
``` 
Action/ 
└── ActionVideoTrait.php        # 操作视频
```

###### 1.1.2.6.2. Activity          营销活动
``` 
Activity/ 
├── ActivityCouponTrait.php                     # 优惠券活动复用类
├── ActivityCouponUsedTrait.php                 # 优惠券使用记录复用类
├── ActivityFdContentTrait.php                  # 满减活动内容复用类
├── ActivityFdTrait.php                         # 满减活动复用类
├── ActivityFdUsedTrait.php                     # 满减活动使用记录复用类
├── ActivityGoodsTrait.php                      # 活动适用商品复用类
├── ActivityLotteryConfigTrait.php              # 付费抽奖配置复用类
├── ActivityLotteryContentTrait.php             # 付费抽奖规则复用类
├── ActivityLotteryTrait.php                    # 付费抽奖活动复用类
├── ActivityLotteryUsedGoodsTrait.php           # 付费抽奖使用记录商品复用类
├── ActivityLotteryUsedTrait.php                # 付费抽奖使用记录复用类
├── ActivityMachineTrait.php                    # 活动适用设备复用类
├── ActivityPickCodeTrait.php                   # 提货码活动券码复用类
└── ActivityPickTrait.php                       # 提货码活动复用类
```

###### 1.1.2.6.3. Advertisement             广告
``` 
Advertisement/ 
├── AdvertisementPushTrait.php          # 广告推送复用类
└── AdvertisementRecordTrait.php        # 广告播放记录复用类
```

###### 1.1.2.6.4. Ali               [废弃]支付宝
``` 
Ali/ 
└── AliBalanceTrait.php 
```

###### 1.1.2.6.5. Api              对外API接口
``` 
Api/ 
├── ApiAdvanceTrait.php             # API预订商品记录表
└── ApiCallbackTrait.php            # 异步通知记录表
```

###### 1.1.2.6.6. Auth              账号权限
``` 
Auth/ 
├── AuthManagerLogTrait.php                     # 用户操作日志复用类
├── AuthManagerMachineTrait.php                 # 管理员绑定设备复用类
├── AuthManagerRoleTrait.php                    # 管理员账号关联权限角色复用类
├── AuthManagerTrait.php                        # 管理员复用类
├── AuthNodeTrait.php                           # 权限节点复用类
├── AuthOrganizationRoleTrait.php               # 组织关联权限角色复用类
├── AuthOrganizationTrait.php                   # 组织架构复用类
├── AuthRoleNodeTrait.php                       # 权限角色关联节点复用类
└── AuthRoleTrait.php                           # 权限角色复用类
```

###### 1.1.2.6.7. Config            配置
``` 
Config/ 
├── ConfigApiTrait.php                          # API对外用户复用类
├── ConfigLangTrait.php                         # 系统语言配置复用类
├── ConfigPerformanceTrait.php                  # 多语言性能参数复用类
├── ConfigSceneLangTrait.php                    # 场景多语言复用类
├── ConfigSceneTrait.php                        # 场景复用类
├── ConfigSizeTrait.php                         # 尺寸复用类
└── ConfigTrait.php                             # 系统配置复用类 
```

###### 1.1.2.6.8. Earth             地球数据
``` 
Earth/ 
├── EarthAreaTrait.php                          # 国内大区复用类
├── EarthCitiesTrait.php                        # 城市数据复用类
├── EarthContinentsTrait.php                    # 全球大区复用类
├── EarthCountriesTrait.php                     # 全球国家复用类
├── EarthRegionsTrait.php                       # 城市地区表复用类
├── EarthStatesTrait.php                        # 全球州省复用类
└── EarthTimezoneTrait.php                      # 时区复用类
```

###### 1.1.2.6.9. Email             邮件
``` 
Email/ 
├── EmailConfigTrait.php                        # 邮件配置复用类
├── EmailTemplateLogTrait.php                   # 邮件模板发送日志复用类
└── EmailTemplateTrait.php                      # 邮件模板复用类
```

###### 1.1.2.6.10. Export           导出
``` 
Export/ 
└── ExportLogTrait.php          # 导出日志复用类
```

###### 1.1.2.6.11. GatewayWorker    [未使用]Socket通讯
``` 
GatewayWorker/ 
└── GatewayWorkerTrait.php
```

###### 1.1.2.6.12. Goods            商品
``` 
Goods/ 
├── GoodsCategoryLangTrait.php                  # 商品分类多语言复用类
├── GoodsCategoryTrait.php                      # 商品分类复用类
├── GoodsChangeTrait.php                        # 商品变化事件记录复用类
├── GoodsCornerTrait.php                        # 商品角标复用类
├── GoodsHitTrait.php                           # 商品设备点击数复用类
├── GoodsLangTrait.php                          # 商品多语言复用类
├── GoodsMultipleGoodsTrait.php                 # 组合商品详情复用类
├── GoodsMultipleMachineTrait.php               # 组合商品设备复用类
├── GoodsMultipleTrait.php                      # 组合商品复用类
└── GoodsTrait.php                              # 复用类
```

###### 1.1.2.6.13. Machine              设备
``` 
Machine/ 
├── MachineChannelReplenishmentTrait.php        # 货道补货记录复用类
├── MachineChannelStockReportTrait.php          # 库存报表视图复用类
├── MachineChannelStockTrait.php                # 库存报表复用类
├── MachineChannelTrait.php                     # 设备货道复用类
├── MachineCheckStockCountTrait.php             # 设备库存盘点记录视图复用类
├── MachineCheckStockTrait.php                  # 设备库存盘点记录复用类
├── MachineConfigLangTrait.php                  # 设备配置多语言复用类
├── MachineConfigTrait.php                      # 设备配置复用类
├── MachineErrorCodeSolutionTrait.php           # 错误码解决方案复用类
├── MachineErrorCodeTrait.php                   # 设备错误码记录复用类
├── MachineGoodsTrait.php                       # 设备商品库复用类
├── MachineGroupLangTrait.php                   # 设备分组多语言复用类
├── MachineGroupMgTrait.php                     # 设备与分组多对多关联复用类
├── MachineGroupTrait.php                       # 设备分组复用类
├── MachineHelpTrait.php                        # 设备帮助内容复用类
├── MachineInfoTrait.php                        # 设备信息复用类
├── MachineLangTrait.php                        # 设备多语言复用类
├── MachineMqRecordTrait.php                    # 设备MQ通讯记录复用类
├── MachineOnOffTrait.php                       # 设备定时任务复用类
├── MachineOnlineDetailsTrait.php               # 设备终端在线时长详情复用类
├── MachineOnlineTrait.php                      # 设备终端在线日志复用类
├── MachineTrait.php                            # 设备主信息复用类
├── MachineVersionPlanTrait.php                 # 设备更新软件版本计划复用类
├── MachineVersionTrait.php                     # 设备软件复用类
└── MachineViewTrait.php                        # 设备视图复用类
```

###### 1.1.2.6.14. MicroMall            微商城
``` 
MicroMall/ 
├── MicroMallMachineTrait.php           # 微商城绑定设备
└── MicroMallTrait.php                  # 微商城
```

###### 1.1.2.6.15. Mq                   MQ
``` 
Mq/ 
└── OutGoodsTrait.php                   # 出货结果处理
```

###### 1.1.2.6.16. Payment              支付
``` 
Payment/ 
├── AfterOrderPaymentTrait.php          # 订单支付后处理
├── AfterOrderRefundTrait.php           # 订单退款后处理
├── AliPayTrait.php                     # 支付宝支付
├── BeforeOrderPaymentTrait.php         # 订单支付前处理
├── BeforeOrderRefundTrait.php          # 订单退款前处理
├── JdCashierTrait.php                  # 京东收银支付
├── TripPay.php                         # 丽呈小程序
└── WxPayTrait.php                      # 微信支付
```

###### 1.1.2.6.17. Resource             素材库
``` 
Resource/ 
└── ResourceTrait.php                   # 素材库
```

###### 1.1.2.6.18. Robot                机器人
``` 
Robot/ 
└── RobotPositionTrait.php              # 机器人位置检测
```

###### 1.1.2.6.19. SaleOrders           销售订单
``` 
SaleOrders/ 
├── SaleHotelNightlyTrait.php           # 酒店每日价格
├── SaleHotelTrait.php                  # 酒店订单
├── SaleOrdersDailyCountTrait.php       # 日销售统计数据视图
├── SaleOrdersGoodsCountTrait.php       # 商品销售统计数据视图
├── SaleOrdersMachineCountTrait.php     # 设备销售统计数据视图
├── SaleOrdersRefundTrait.php           # 销售订单退款
├── SaleOrdersRevenueTrait.php          # 销售订单分润
├── SaleOrdersTrait.php                 # 销售订单
├── SaleOrdersUnclaimedTrait.php        # 销售订单未取商品
└── SaleOrdersVideoTrait.php            # 销售订单交易视频记录
```

###### 1.1.2.6.20. Send                 发送
``` 
Send/ 
└── ToManagerTrait.php                  # 发送给账号
```

###### 1.1.2.6.21. Strategy             策略
``` 
Strategy/ 
├── StrategyIncomeTrait.php             # 分账策略
├── StrategyMachineTrait.php            # 策略绑定设备
├── StrategyManagerTrait.php            # 策略绑定账号
└── StrategyPayeeTrait.php              # 收款策略
```

###### 1.1.2.6.22. Suggest              意见与建议
``` 
Suggest/ 
└── SuggestTrait.php                    # 意见与建议
```

###### 1.1.2.6.23. Template             模板
``` 
Template/ 
├── TemplateLayoutTrait.php             # 模板布局
├── TemplatePluginsTrait.php            # 模板插件
├── TemplateTrait.php                   # 模板
└── TemplateViewTrait.php               # 模板视图
```

###### 1.1.2.6.24. Trip                 丽呈小程序
``` 
Trip/ 
├── TripCityTrait.php                   # 携程城市信息
├── TripMultipleGoodsTrait.php          # 携程套餐商品
├── TripMultipleHotelTrait.php          # 携程酒店套餐商品酒店
├── TripMultipleMachineTrait.php        # 携程组合商品设备
└── TripMultipleTrait.php               # 携程套餐商品
```

###### 1.1.2.6.25. UpdateLog            更新日志
``` 
UpdateLog/ 
└── UpdateLogTrait.php                  # 更新日志
```

###### 1.1.2.6.26. User                 用户
``` 
User/ 
└── UserTrait.php                       # 用户表
```

###### 1.1.2.6.27. Wx                   微信
``` 
Wx/ 
├── WxOfficialLoginTrait.php            # 微信扫码登录日志
├── WxOfficialTrait.php                 # 微信公众号
├── WxTemplateLogTrait.php              # 微信消息模板通知日志
└── WxTemplateTrait.php                 # 微信消息模板
```

##### 1.1.2.7. Util                     静态类
``` 
Util/ 
├── SignUtil.php                        # 加签验签
└── TDESUtil.php                        # 加密解密
```

#### 1.1.3. Machine                     系统与设备终端通讯
``` 
Machine/ 
├── Receive/                            # 系统接收终端数据
├── Send/                               # 系统下发数据给终端
├── Application.php                     # 程序入口
└── MachineBaseClient.php               # 设备基础处理类
```

##### 1.1.3.1. Receive                  系统接收设备数据
``` 
Receive/ 
├── ActivityClient.php                  # 营销活动业务
├── ApiClient.php                       # API业务
├── HotelClient.php                     # 酒店业务
├── MicroMallClient.php                 # 微商城业务
├── MqClient.php                        # MQ通讯业务
├── ReceiveBaseClient.php               # 接收数据基础类
├── RobotClient.php                     # 机器人业务
└── SaleOrdersClient.php                # 销售订单业务
```

##### 1.1.3.2. Send                     系统下发数据给终端
``` 
Send/ 
├── MqClient.php                        # MQ通讯下发
└── SendBaseClient.php                  # 下发数据基础类
```

#### 1.1.4. Management                  管理后台
``` 
Management/ 
├── Action/                             # 操作相关
├── Activity/                           # 营销活动
├── Advertisement/                      # 广告
├── Auth/                               # 权限
├── Common/                             # 公用
├── Config/                             # 配置
├── Earth/                              # 地球
├── Email/                              # 邮件
├── Export/                             # 导出
├── Goods/                              # 商品
├── Hotel/                              # 酒店
├── Index/                              # 首页
├── Login/                              # 登录
├── Machine/                            # 设备
├── MicroMall/                          # 微商城
├── Resource/                           # 素材库
├── Sale/                               # 销售订单
├── Strategy/                           # 策略
├── Suggest/                            # 意见与建议
├── Template/                           # 模板
├── Trip/                               # 丽呈小程序业务
├── UpdateLog/                          # 更新日志
├── Wx/                                 # 微信
├── Application.php                     # 程序入口
└── ManagementClient.php                # 管理后台基础类
```

##### 1.1.4.1. Action                   操作相关
``` 
Action/ 
└── ActionVideoClient.php               # 操作视频
```

##### 1.1.4.2. Activity                 营销活动
``` 
Activity/ 
├── ActivityCouponClient.php            # 优惠券管理
├── ActivityCouponUsedClient.php        # 优惠券使用记录管理
├── ActivityFdClient.php                # 满减活动
├── ActivityFdUsedClient.php            # 满减活动使用记录
├── ActivityLotteryClient.php           # 付费抽奖
├── ActivityLotteryUsedClient.php       # 付费抽奖使用记录
├── ActivityPickClient.php              # 提货码活动
└── ActivityPickCodeClient.php          # 提货码券码
```

##### 1.1.4.3. Advertisement            广告
``` 
Advertisement/ 
├── AdvertisementPushClient.php         # 广告推送
└── AdvertisementRecordClient.php       # 广告播放记录
```

##### 1.1.4.4. Auth                     账号权限
``` 
Auth/ 
├── AuthManagerClient.php               # 账号
├── AuthManagerLogClient.php            # 账号操作日志
├── AuthManagerMachineClient.php        # 账号绑定设备
├── AuthManagerRoleClient.php           # 账号绑定权限角色
├── AuthNodeClient.php                  # 权限菜单
├── AuthOrganizationClient.php          # 组织架构
├── AuthOrganizationRoleClient.php      # 组织架构绑定权限角色
├── AuthRoleClient.php                  # 权限角色
└── AuthRoleNodeClient.php              # 权限角色绑定权限菜单
```

##### 1.1.4.5. Common                   公用类库
``` 
Common/ 
└── CityClient.php                      # 国内省市区数据
```

##### 1.1.4.6. Config                   系统配置
``` 
Config/ 
├── ConfigApiClient.php                 # 对外API配置
├── ConfigClient.php                    # 系统配置
├── ConfigLangClient.php                # 系统配置多语言数据
├── ConfigPerformanceClient.php         # 性能参数多语言配置
├── ConfigSceneClient.php               # 场景配置
└── ConfigSizeClient.php                # 尺寸配置
```

##### 1.1.4.7. Earth                    地球数据业务
``` 
Earth/ 
└── EarthClient.php                     # 地球数据类
```

##### 1.1.4.8. Email                    邮件
``` 
Email/ 
├── EmailConfigClient.php               # 邮件配置
├── EmailTemplateClient.php             # 邮件模板
└── EmailTemplateLogClient.php          # 邮件通知日志记录
```

##### 1.1.4.9. Export                   导出Excel
``` 
Export/ 
└── ExportLogClient.php                 # 导出日志
```

##### 1.1.4.10. Goods                   商品
``` 
Goods/ 
├── GoodsCategoryClient.php             # 商品分类 
├── GoodsCategoryLangClient.php         # 商品分类多语言
├── GoodsChangeClient.php               # 商品变化事件
├── GoodsClient.php                     # 商品
├── GoodsCornerClient.php               # 商品角标
├── GoodsHitClient.php                  # 商品互动
├── GoodsLangClient.php                 # 商品多语言
└── GoodsMultipleClient.php             # 组合商品
```

##### 1.1.4.11. Hotel                   酒店业务
``` 
Hotel/ 
└── HotelClient.php                     # 酒店业务
```

##### 1.1.4.12. Index                   首页
``` 
Index/ 
└── SaleDataClient.php                  # 首页数据统计
```

##### 1.1.4.13. Login                   登录
``` 
Login/ 
└── LoginClient.php                     # 登录业务
```

##### 1.1.4.14. Machine                 设备管理
``` 
Machine/ 
├── MachineChannelClient.php                        # 设备货道
├── MachineChannelReplenishmentClient.php           # 设备货道补货
├── MachineChannelStockClient.php                   # 库存报表
├── MachineChannelStockReportClient.php             # 盘点报表
├── MachineCheckStockClient.php                     # 设备库存盘点记录
├── MachineCheckStockCountClient.php                # 设备库存盘点汇总
├── MachineClient.php                               # 设备
├── MachineConfigClient.php                         # 设备配置
├── MachineConfigLangClient.php                     # 设备配置多语言数据
├── MachineErrorCodeClient.php                      # 错误码
├── MachineErrorCodeSolutionClient.php              # 错误码解决方案
├── MachineGoodsClient.php                          # 设备商品库
├── MachineGroupClient.php                          # 设备分组
├── MachineGroupLangClient.php                      # 设备分组多语言
├── MachineGroupMgClient.php                        # 设备与分组多对多关联
├── MachineHelpClient.php                           # 设备帮助
├── MachineInfoClient.php                           # 设备信息
├── MachineLangClient.php                           # 设备多语言主信息
├── MachineOnOffClient.php                          # 设备营业配置
├── MachineOnlineClient.php                         # 设备在线汇总
├── MachineOnlineDetailsClient.php                  # 设备在线详情
├── MachineVersionClient.php                        # 设备软件管理
├── MachineVersionPlanClient.php                    # 设备软件更新计划
└── MachineViewClient.php                           # 设备视图
```

##### 1.1.4.15. MicroMall                           微商城
``` 
MicroMall/ 
├── MicroMallClient.php                             # 微商城配置
└── MicroMallMachineClient.php                      # 微商城绑定设备
```

##### 1.1.4.16. Resource                            素材
``` 
Resource/ 
└── ResourceClient.php                              # 素材库管理
```

##### 1.1.4.17. Sale                                销售订单
``` 
Sale/ 
├── SaleOrdersClient.php                            # 销售订单
└── SaleOrdersUnclaimedClient.php                   # 销售订单未取记录
```

##### 1.1.4.18. Strategy                            策略
``` 
Strategy/ 
├── StrategyIncomeClient.php                        # 分润策略
├── StrategyMachineClient.php                       # 策略绑定设备
├── StrategyManagerClient.php                       # 策略绑定账号
└── StrategyPayeeClient.php                         # 收款策略
```

##### 1.1.4.19. Suggest                             建议与意见
``` 
Suggest/ 
└── SuggestClient.php                               # 建议与意见
```

##### 1.1.4.20. Template                            模板
``` 
Template/ 
├── TemplateClient.php                              # 模板
├── TemplateLayoutClient.php                        # 模板布局
├── TemplatePluginsClient.php                       # 模板组件
└── TemplateViewClient.php                          # 模板视图
```

##### 1.1.4.21. Trip                                丽呈小程序相关
``` 
Trip/ 
├── TripMultipleClient.php                          # 携程套餐
├── TripMultipleGoodsClient.php                     # 携程套餐商品
├── TripMultipleHotelClient.php                     # 携程套餐酒店
└── TripMultipleMachineClient.php                   # 携程套餐绑定设备
```

##### 1.1.4.22. UpdateLog                           更新日志
``` 
UpdateLog/ 
└── UpdateLogClient.php                             # 更新日志
```

##### 1.1.4.23. Wx                                  微信
``` 
Wx/ 
├── WxOfficialClient.php                            # 微信公众号
├── WxTemplateClient.php                            # 微信公众号消息模板
└── WxTemplateLogClient.php                         # 微信公众号消息模板日志
```

#### 1.1.5. Mobile                  手机端H5业务
``` 
Mobile/ 
├── Machine/                                        # 设备数据
├── Application.php                                 # 程序入口
└── MobileBase.php                                  # 手机端基础类
```

##### 1.1.5.1. Machine                  设备数据
``` 
Machine/ 
├── CheckClient.php                                 # 库存盘点
└── InfoClient.php                                  # 信息获取与验证
```

#### 1.1.6. Notice                  通知
``` 
Notice/ 
├── Email/                                          # 邮件通知业务
├── WeChat/                                         # 微信通知业务
├── Application.php                                 # 程序入口
└── NoticeBaseClient.php                            # 通知基础类
```

##### 1.1.6.1. Email                    邮件通知
``` 
Email/ 
└── EmailClient.php                                 # 邮件通知类
```

##### 1.1.6.2. WeChat                   微信通知
``` 
WeChat/ 
└── WeChatClient.php                                # 微信通知类
```

#### 1.1.7. Pay                     支付业务
``` 
Pay/ 
├── Notify/                                         # 回调通知业务
├── SaleOrders/                                     # 销售订单业务
├── Application.php                                 # 程序入口
└── PayBaseClient.php                               # 支付业务基础类
```

##### 1.1.7.1. Notify                   回调通知
``` 
Notify/ 
├── AliClient.php                                   # 支付宝业务
├── JdCashierClient.php                             # 京东收银业务
└── WxClient.php                                    # 微信业务
```

##### 1.1.7.2. SaleOrders               销售订单
``` 
SaleOrders/ 
└── PaymentClient.php                               # 销售订单发起支付业务
```

#### 1.1.8. RabbitMq                RabbitMQ数据处理业务
``` 
RabbitMq/ 
├── MqConsumer.php                                  # MQ消费者业务
└── MqProducer.php                                  # MQ生产者业务
```

#### 1.1.9. TimeTask                定时任务
``` 
TimeTask/ 
├── Activity/                                       # 营销活动
├── AuthManager/                                    # 账号权限
├── Export/                                         # 导出Excel
├── Goods/                                          # 商品
├── Machine/                                        # 设备
├── Payment/                                        # 支付
├── Application.php                                 # 程序入口
└── TimeTaskBase.php                                # 定时任务基础类
```

##### 1.1.9.1. Activity             营销活动
``` 
Activity/ 
├── CouponClient.php                                # 优惠券
└── PickCodeClient.php                              # 取货码
```

##### 1.1.9.2. AuthManager          权限账号
``` 
AuthManager/ 
└── AuthManagerLogClient.php                        # 账号操作日志
```

##### 1.1.9.3. Export               导出
``` 
Export/ 
└── ExportClient.php                                # 导出Excel
```

##### 1.1.9.4. Goods                商品
``` 
Goods/ 
└── GoodsClient.php                                 # 商品
```

##### 1.1.9.5. Machine              设备
``` 
Machine/ 
├── MachineChannelStockClient.php                   # [已废弃]获取设备货道库存
└── MachineClient.php                               # 设备
```

##### 1.1.9.6. Payment              支付
``` 
Payment/ 
├── AliClient.php                                   # 支付宝
└── WxClient.php                                    # 微信
```

#### 1.1.10. Wx                 微信
``` 
Wx/ 
├── Official/                                       # 公众号
├── Application.php                                 # 程序入口
└── WxBaseClient.php                                # 公众号基础类
```

##### 1.1.10.1. Official            公众号
``` 
Official/ 
├── LoginClient.php                                 # 扫码登录
├── OfficialClient.php                              # 公众号事件处理
└── OfficialProvider.php                            # 容器provider
```

### 1.2. api            对外API入口
``` 
api/ 
├── controller/                                     # 控制器
├──── Common.php                                    # 公用类
├──── V2.php                                        # V2入口类
├── lang/                                           # 语言包
├──── en.php                                        # 英文语言包
└──── zh-cn.php                                     # 中文语言包
```

### 1.3. command        自定义命令
``` 
command/ 
├── Api.php                                         # 对外API推送数据命令
├── DataUpload.php                                  # 终端MQ上报
├── ExportQueue.php                                 # MQ执行导出Excel
├── Payment.php                                     # 支付守护进程入口
└── TimeTask.php                                    # 定时任务命令
```

### 1.4. http           [已废弃]
``` 
http/ 
├── controller/ 
├──── redis/ 
├────── Machine.php
├────── MicroPay.php
├──── Oauth2.php
└──── Test.php
```

### 1.5. index          首页静态页面
``` 
index/ 
├── controller/ 
├──── Index.php
├── view/ 
├──── index/ 
└────── index.html
```

### 1.6. lang           公用语言包
``` 
lang/ 
├── zh-cn/ 
└──── app.php                                       # 中文语言包
```

### 1.7. machine        终端通讯目录
``` 
machine/ 
├── controller/                                      # 控制器
├── lang/                                            # 语言包
├── validate/                                        # 验证器
└── view/                                            # 视图
```

#### 1.7.1. controller      终端通讯控制器目录
``` 
controller/ 
├── Common.php                                      # 公用类 
├── Hotel.php                                       # 酒店业务
├── Receipt.php                                     # 小票业务
├── Receive.php                                     # 接收终端数据
├── Robot.php                                       # 机器人数据
└── Send.php                                        # [已废弃]
```

#### 1.7.2. lang            终端通讯语言包 
``` 
lang/ 
└── zh-cn.php                                       # 中文语言包
```

#### 1.7.3. validate        终端通讯验证器
``` 
validate/ 
├── VCommon.php                                     # 公用类
├── VHotel.php                                      # 酒店验证器
├── VReceive.php                                    # 接收数据验证器
└── VRobot.php                                      # 接收机器人数据验证器
```

#### 1.7.4. view            视图
``` 
view/ 
├── receipt/                                        # 小票
└──── print.html                                    # 小票模板，动态数据
```

### 1.8. management     管理后台
``` 
management/ 
├── controller/                                     # 控制器
├── lang/                                           # 语言包
└── validate/                                       # 验证器
```

#### 1.8.1. controller      管理后台控制器
``` 
controller/ 
├── action/                                         # 操作相关
├── activity/                                       # 营销活动
├── advertisement/                                  # 广告
├── auth/                                           # 账号权限
├── common/                                         # 公用
├── config/                                         # 配置
├── earth/                                          # 地球
├── email/                                          # 邮件
├── export/                                         # 导出
├── goods/                                          # 商品
├── hotel/                                          # 酒店
├── machine/                                        # 设备
├── microMall/                                      # 微商城
├── resource/                                       # 素材库
├── sale/                                           # 销售订单
├── strategy/                                       # 策略
├── suggest/                                        # 建议及意见
├── template/                                       # 模板
├── trip/                                           # 丽呈
├── updateLog/                                      # 更新日志
├── wx/                                             # 微信
├── AuthController.php                              # 身份信息及权限验证类
├── Common.php                                      # 公共头部类
├── Index.php                                       # 首页
└── Login.php                                       # 登录类
```

##### 1.8.1.1. action               操作相关
``` 
action/ 
└── ActionVideo.php                                 # 操作视频
```

##### 1.8.1.2. activity             营销活动
``` 
activity/ 
├── ActivityCoupon.php                              # 优惠券活动接口
├── ActivityCouponUsed.php                          # 优惠券使用记录接口
├── ActivityFd.php                                  # 满减活动接口
├── ActivityFdUsed.php                              # 满减活动使用接口
├── ActivityLottery.php                             # 付费抽奖接口
├── ActivityLotteryUsed.php                         # 付费抽奖记录接口
├── ActivityPick.php                                # 提货码活动接口
└── ActivityPickCode.php                            # 提货码券码接口
```

##### 1.8.1.3. advertisement                广告
``` 
advertisement/ 
├── AdvertisementPush.php                           # 广告推送接口
└── AdvertisementRecord.php                         # 广告播放记录接口
```

##### 1.8.1.4. auth             账号授权
``` 
auth/ 
├── AuthManager.php                                 # 账号接口
├── AuthManagerLog.php                              # 账号操作日志接口
├── AuthManagerMachine.php                          # 账号绑定设备接口
├── AuthManagerRole.php                             # 账号绑定权限角色接口
├── AuthNode.php                                    # 权限菜单接口
├── AuthOrganization.php                            # 组织架构接口
├── AuthOrganizationRole.php                        # 组织架构绑定权限角色接口
├── AuthRole.php                                    # 权限角色接口
└── AuthRoleNode.php                                # 权限角色绑定权限节点接口
```

##### 1.8.1.5. common           公用库
``` 
common/ 
└── City.php                                        # 国内省市区接口
```

##### 1.8.1.6. config           系统配置
``` 
config/ 
├── Config.php                                      # 系统配置接口
├── ConfigApi.php                                   # 对外API接口
├── ConfigLang.php                                  # 系统配置多语言接口
├── ConfigPerformance.php                           # 性能参数多语言接口
├── ConfigScene.php                                 # 场景接口
└── ConfigSize.php                                  # 尺寸接口
```

##### 1.8.1.7. earth            地球
``` 
earth/ 
├── Country.php                                     # 国家数据接口
└── Timezone.php                                    # 时区接口
```

##### 1.8.1.8. email            邮件
``` 
email/ 
├── EmailConfig.php                                 # 邮件配置
├── EmailTemplate.php                               # 邮件模板配置
└── EmailTemplateLog.php                            # 邮件模板多语言配置
```

##### 1.8.1.9. export           导出Excel
``` 
export/ 
└── ExportLog.php                                   # 导出Excel日志接口
```

##### 1.8.1.10. goods           商品
``` 
goods/ 
├── Goods.php                                       # 商品接口
├── GoodsCategory.php                               # 商品分类接口
├── GoodsCategoryLang.php                           # 商品分类多语言接口
├── GoodsChange.php                                 # 商品变化事件记录接口
├── GoodsCorner.php                                 # 商品角标接口
├── GoodsHit.php                                    # 商品互动接口
├── GoodsLang.php                                   # 商品多语言接口
└── GoodsMultiple.php                               # 组合商品接口
```

##### 1.8.1.11. hotel           携程酒店
``` 
hotel/ 
└── Hotel.php                                       # 携程酒店接口
```

##### 1.8.1.12. machine         设备
``` 
machine/ 
├── Machine.php                                     # 设备接口  
├── MachineAdvance.php                              # 设备推送广告接口
├── MachineChannel.php                              # 设备货道接口
├── MachineChannelReplenishment.php                 # 设备货道补货接口
├── MachineChannelStock.php                         # 设备货道库存接口
├── MachineChannelStockReport.php                   # 设备库存盘点报表接口
├── MachineCheckStock.php                           # 设备库存盘点接口
├── MachineCheckStockCount.php                      # 设备库存盘点汇总视图接口
├── MachineConfig.php                               # 设备配置接口
├── MachineConfigLang.php                           # 设备配置多语言接口
├── MachineErrorCode.php                            # 错误码接口
├── MachineErrorCodeSolution.php                    # 错误码解决方案接口
├── MachineGoods.php                                # 设备商品接口
├── MachineGroup.php                                # 设备分组接口
├── MachineGroupLang.php                            # 设备分组多语言接口
├── MachineGroupMg.php                              # 设备与分组多对多关联接口
├── MachineHelp.php                                 # 设备帮助信息接口
├── MachineInfo.php                                 # 设备信息接口
├── MachineLang.php                                 # 设备多语言接口
├── MachineOnOff.php                                # 设备营业配置接口
├── MachineOnline.php                               # 设备在线汇总接口
├── MachineOnlineDetails.php                        # 设备在线详情接口
├── MachineVersion.php                              # 设备软件版本接口
├── MachineVersionPlan.php                          # 设备软件版本更新日志接口
└── MachineView.php                                 # 设备视图接口
```

##### 1.8.1.13. microMall       微商城
``` 
microMall/ 
└── MicroMall.php                                   # 微商城接口
```

##### 1.8.1.14. resource        素材库
``` 
resource/ 
└── Resource.php                                    # 素材库接口
```

##### 1.8.1.15. sale            销售订单
``` 
sale/ 
├── SaleOrders.php                                  # 销售订单接口
└── SaleOrdersUnclaimed.php                         # 销售订单未取商品接口
```

##### 1.8.1.16. strategy        策略管理
``` 
strategy/ 
├── StrategyIncome.php                              # 分账策略接口
├── StrategyMachine.php                             # 策略绑定设备接口
├── StrategyManager.php                             # 策略绑定账号接口
└── StrategyPayee.php                               # 收款策略接口
```

##### 1.8.1.17. suggest         建议与意见
``` 
suggest/ 
└── Suggest.php                                     # 建议与意见接口
```

##### 1.8.1.18. template        设备模板
``` 
template/ 
├── Template.php                                    # 模板接口
├── TemplateLayout.php                              # 模板布局接口
├── TemplatePlugins.php                             # 模板组件接口
└── TemplateView.php                                # 模板视图接口
```

##### 1.8.1.19. trip           丽呈小程序配置
``` 
trip/ 
├── TripMultiple.php                                # 携程套餐接口
├── TripMultipleGoods.php                           # 携程套餐商品接口
├── TripMultipleHotel.php                           # 携程套餐酒店接口
└── TripMultipleMachine.php                         # 携程套餐绑定设备接口
```

##### 1.8.1.20. updateLog       更新日志
``` 
updateLog/ 
└── UpdateLog.php                                   # 更新日志接口
```

##### 1.8.1.21. wx              微信配置
``` 
wx/ 
├── WxOfficial.php                                  # 微信公众号配置接口
├── WxTemplate.php                                  # 微信公众号消息模板配置接口
└── WxTemplateLog.php                               # 微信公众号消息模板通知日志接口
```

#### 1.8.2. lang                语言包
``` 
lang/ 
└── zh-cn.php                                       # 中文语言包
```

#### 1.8.3. validate            验证器
``` 
validate/ 
├── Action/                                         # 操作相关验证器目录
├── Activity/                                       # 营销活动验证器目录
├── Config/                                         # 系统配置验证器目录
├── Email/                                          # 邮件验证器目录
├── Goods/                                          # 商品验证器目录
├── Machine/                                        # 设备验证器目录
├── MicroMall/                                      # 微商城验证器目录
├── Trip/                                           # 携程验证器目录
├── Wx/                                             # 微信验证器目录
├── VAdvertisement.php                              # 广告验证器
├── VAuth.php                                       # 权限验证器
├── VCommon.php                                     # 公用验证器
├── VGoods.php                                      # 商品验证器
├── VGoodsCategory.php                              # 商品分类验证器
├── VGoodsCategoryLang.php                          # 商品分类多语言验证器
├── VGoodsCorner.php                                # 商品角标验证器
├── VGoodsLang.php                                  # 商品多语言验证器
├── VHotel.php                                      # 携程酒店验证器
├── VLogin.php                                      # 登录验证器
├── VResource.php                                   # 素材库验证器
├── VSaleOrders.php                                 # 订单验证器
├── VSaleOrdersUnclaimed.php                        # 未取商品验证器
├── VStrategyIncome.php                             # 分润策略验证器
├── VStrategyMachine.php                            # 策略绑定设备验证器
├── VStrategyPayee.php                              # 收款策略验证器
├── VSuggest.php                                    # 建议与意见验证器
├── VTemplate.php                                   # 模板验证器
├── VTemplateLayout.php                             # 模板布局验证器
├── VTemplatePlugins.php                            # 模板组件验证器
├── VTemplateView.php                               # 模板视图验证器
└── VUpdateLog.php                                  # 更新日志验证器
```

##### 1.8.3.1. Action               操作相关
``` 
Action/ 
└── VActionVideo.php                                # 操作视频验证器
```

##### 1.8.3.2. Activity
``` 
Activity/ 
├── VActivityCoupon.php                             # 优惠券验证器
├── VActivityCouponUsed.php                         # 优惠券使用日志验证器
├── VActivityFd.php                                 # 满减活动验证器
├── VActivityLottery.php                            # 付费抽奖验证器
├── VActivityPick.php                               # 提货码验证器
└── VActivityPickCode.php                           # 提货码券码验证器
```

##### 1.8.3.3. Config               系统配置验证器目录
``` 
Config/ 
├── VConfig.php                                     # 系统配置验证器
├── VConfigApi.php                                  # 对外API配置验证器
├── VConfigLang.php                                 # 系统配置多语言验证器
├── VConfigPerformance.php                          # 性能参数多语言验证器
├── VConfigScene.php                                # 场景验证器
└── VConfigSize.php                                 # 尺寸验证器
```

##### 1.8.3.4. Email                邮件配置验证器目录
``` 
Email/ 
├── VEmailConfig.php                                # 邮件配置验证器
└── VEmailTemplate.php                              # 邮件模板验证器
```

##### 1.8.3.5. Goods                商品验证器目录
``` 
Goods/ 
├── VGoodsChange.php                                # 商品变化记录日志验证器
└── VGoodsMultiple.php                              # 组合商品验证器
```

##### 1.8.3.6. Machine              设备验证器目录
``` 
Machine/ 
├── VMachine.php                                    # 设备验证器
├── VMachineAdvance.php                             # 设备推送广告验证器
├── VMachineChannel.php                             # 设备货道验证器
├── VMachineConfig.php                              # 设备配置验证器
├── VMachineConfigLang.php                          # 设备配置多语言验证器
├── VMachineErrorCodeSolution.php                   # 错误码验证器
├── VMachineGoods.php                               # 设备商品库验证器
├── VMachineGroup.php                               # 设备分组验证器
├── VMachineGroupLang.php                           # 设备分组多语言验证器
├── VMachineGroupMg.php                             # 设备与分组多对多关联验证器
├── VMachineHelp.php                                # 设备帮助验证器
├── VMachineInfo.php                                # 设备信息验证器
├── VMachineLang.php                                # 设备多语言验证器
├── VMachineOnOff.php                               # 设备营业配置验证器
├── VMachineVersion.php                             # 设备软件版本验证器
├── VMachineVersionPlan.php                         # 设备软件版本更新计划验证器
└── VMachineView.php                                # 验证器
```

##### 1.8.3.7. MicroMall            微商城验证器目录
``` 
MicroMall/ 
└── VMicroMall.php                                  # 微商城信息验证器
```

##### 1.8.3.8. Trip                 携程验证器目录
``` 
Trip/ 
├── VTripMultiple.php                               # 携程套餐验证器
├── VTripMultipleGoods.php                          # 携程套餐商品验证器
├── VTripMultipleHotel.php                          # 携程套餐酒店验证器
└── VTripMultipleMachine.php                        # 携程套餐绑定设备验证器
```

##### 1.8.3.9. Wx                   微信验证器目录
``` 
Wx/ 
├── VWxOfficial.php                                 # 公众号验证器
└── VWxTemplate.php                                 # 公众号消息模板验证器
```

### 1.9. mobile                     手机端H5页面
``` 
mobile/ 
├── controller/                                     # 控制器
├──── machine/                                      # 设备信息目录
├────── Check.php                                   # 库存盘点控制器 
├────── Info.php                                    # 设备信息控制器
├──── Common.php                                    # 公用控制器
├── lang/                                           # 语言包
├──── zh-cn.php                                     # 中文
├── validate/                                       # 验证器
├──── Machine/                                      # 设备
└────── VMachineCheck.php                           # 库存盘点验证器
└──── VCommon.php                                   # 公用验证器
```

### 1.10. pay           支付目录
``` 
pay/ 
├── controller/                                     # 控制器
├──── notify/                                       # 支付回调处理
├────── Ali.php                                     # 支付宝回调处理
├────── JdCashier.php                               # 京东收银回调处理
├────── Wx.php                                      # 微信支付回调处理
├──── Common.php                                    # 公用类
├──── Payment.php                                   # 发起支付
├── lang/                                           # 语言包
├──── zh-cn.php                                     # 中文
├── validate/                                       # 验证器
├──── VCommon.php                                   # 公用验证器
└──── VSaleOrder.php                                # 订单验证器
```

### 1.11. wx            微信目录
``` 
wx/ 
├── controller/                                     # 控制器
├──── Login.php                                     # 授权登录
├──── Official.php                                  # 公众号事件接收入口
├── lang/                                           # 语言包
├──── zh-cn.php                                     # 中文
├── validate/                                       # 验证器
├──── VCommon.php                                   # 公共验证器
└──── VLogin.php                                    # 授权登录验证器
```

## 2. extend            自定义类库
```
extend/
├── AliPay/                                         # 支付宝SDK包
├── Jd/                                             # 京东收银SDK包
├── PHPExcel/                                       # Excel管理SDK包
└── WeChatPayV3/                                    # 微信V3版本支付SDK包
```

## 3. config            框架配置
``` 
config/ 
├── app.php                                         # 应用配置文件
├── auth_manager_log_list.php                       # 用户操作日志配置文件
├── cache.php                                       # 缓存配置文件
├── captcha.php                                     # 验证码配置文件
├── console.php                                     # 输出配置文件
├── cookie.php                                      # cookie配置文件
├── database.php                                    # 数据库配置文件
├── filesystem.php                                  # 文件管理配置文件
├── gateway_worker.php                              # socket配置文件
├── lang.php                                        # 语言包配置文件
├── log.php                                         # 框架日志配置文件
├── middleware.php                                  # 中间件配置文件
├── queue.php                                       # 队列配置文件
├── rabbit_mq.php                                   # RabbitMQ配置文件
├── receipt.php                                     # 小票配置文件
├── redis.php                                       # redis配置文件
├── route.php                                       # 路由配置文件
├── session.php                                     # Session配置文件
├── trace.php                                       # 异常处理配置文件
├── view.php                                        # 视图配置文件
├── worker.php                                      # workerman配置文件
└── worker_server.php                               # workerman服务端配置文件
```

## 4. public            [Web入口] 对外公开目录
``` 
public/ 
├── export/                                         # 导出Excel文件目录
├── h5/ 
├── mobile/                                         # 手机端库存盘点目录
├── static/                                         # 静态资源目录
├── vue/                                            # 管理后台前端文件目录
├── .htaccess                                       # 伪静态
├── favicon.ico                                     # ico图标
├── index.php                                       # 框架入口文件
├── mqtt.php                                        # [未启用]MQTT启动文件
├── robots.txt                                      # 路由
└── router.php                                      # 路由入口
```


## 5. route             路由目录
``` 
route/ 
└── app.php                                         # 应用程序路由
```

## 6. runtime           [运行时] 缓存/日志等
``` 
runtime/ 
├── cache/                                        # 缓存存储目录
├── log/                                          # 日志存储目录
├── temp/                                         # 视图模板存储目录
└── ……                                          # 其他各功能日志或缓存存储目录
```

## 7. vendor             [依赖] Composer 包目录
``` 
route/ 
└── ……                                           # Composer安装的依赖包，包含框架核心等
```
