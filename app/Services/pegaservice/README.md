# PegaService 子模块

这是 PegaService 子模块，用于与 Line Bot 集成，实现用户查询、数据库操作等功能。

## 1. 安装与初始化

```bash
git submodule update --init --recursive
```

## 2. 更新子模块
```bash
cd app/Services/pegaservice/pegaservice
git pull origin main   # 或者指定分支
cd ../../../..         # 回到主项目目录
git add app/Services/pegaservice/pegaservice
git commit -m "update pegaservice submodule"
```

3. 使用说明
line公式账号上发送以下消息

- pega --action=dbInit
- pega --action=queryUserByDateLimit --status=A --dateStart=2025/09/01 --dateEnd=2025/09/02
- pega --action=queryUserByDateLimit --status=B --dateStart=2025/10/01 --dateEnd=2025/10/05

## 4. 注意事项
1.	子模块必须初始化，否则主项目无法调用 PegaReplyEngine。
2.	每次主项目更新或子模块更新后，务必执行 git submodule update --init --recursive。