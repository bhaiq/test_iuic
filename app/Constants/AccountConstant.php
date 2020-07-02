<?php
/**
 * Project: dayi-server
 * User: yongsheng.li
 * Date: 25/08/2017
 * Time: 1:50 PM
 */

namespace App\Constants;


class AccountConstant
{
    /**
     * 用户有效状态
     */
    const STATE_INACTIVE                = 0; // 未激活
    const STATE_ACTIVE                  = 1; // 有效
    const STATE_DISABLED                = 2; // 禁用

    /**
     * 用户类型
     */
    const TYPE_NORMAL                   = 0; // 学生
    const TYPE_TEACHER                  = 1; // 老师
    const TYPE_STUDENT                  = 2; // 学生
    const TYPE_PARENT                   = 3; // 家长
    const TYPE_CREATION                 = 8; // 资源中心
    const TYPE_ADMIN                    = 9; // 管理员

    /**
     * 用户角色
     */
    const ROLE_CREATION_VENDOR          = 0; // 资源中心兼职
    const ROLE_CREATION_TEACHING        = 1; // 资源中心教研
    const ROLE_CREATION_ADMIN           = 9; // 资源中心管理员

    /**
     * 平台
     */
    const CLIENT_WEB                    = 0; // 网站
    const CLIENT_WEB_ADMIN              = 1; // 管理员
    const CLIENT_WEB_CREATION           = 2; // 资源创作中心
    const CLIENT_WEB_SLIDE              = 3; // 编辑器
    const CLIENT_WEB_TEACHER            = 4; // 教师端
    const CLIENT_APP_STUDENT            = 5; // APP学生端

}