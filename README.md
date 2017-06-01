# Hongpa

一个基于 **Swoole 2.0 协程** 的API框架

## 特性
- 隐式协程切换
- 对象容器
- mysql连接池
- redis连接池
- 事件
- 命令行交互
- docker开发环境
- 代码风格自动修复
- 单元测试

## 快速上手
###运行开发环境
1. 下载安装 docker
2. cd 进入根目录
3. `docker-compose up -d` 启动相关docker容器
在docker中使用命令行工具：
`./docker/hong`

###Hello World
1.在 IndexController 中加入
```
public function hello()
{
	return 'hello world';
}
```
2.执行 `docker-compose restart php` 重启swoole
3.`curl http://127.0.0.1:9050/index/hello/`

###关于Hongpa
项目仍处于开发中，会存在一些不完善或者不规范之处，欢迎PR。