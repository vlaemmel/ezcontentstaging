<?php /*

[Authentication]
# disabled all authentication and force admin user for now
# should be implemented perhaps in the provider
# or with a custom AuthenticationStyle
RequireAuthentication=disabled
RequireHTTPS=disabled
DefaultUserID=14

[ApiProvider]
ProviderClass[contentstaging]=contentStagingRestApiProvider

[RouteSettings]
# Skip (auth) filter for every action in 'myController' which is of API version 2
SkipFilter[]=contentStagingRestApiController_*

[contentStagingRestContentController_remove_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_addLocation_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_hideUnhide_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_move_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_removeLocation_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_removeTranslation_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_updateSection_CacheSettings]
ApplicationCache=disabled

[contentStagingRestContentController_updateSort_CacheSettings]
ApplicationCache=disabled

*/ ?>
