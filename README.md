# wd-cms

## Requirement
- PHP ^7
- symfony ^4
- sonata admin and media bundle

## Installation
Add the repo to your composer.json

```json
"repositories": [
	 {
	   "type": "git",
	   "url": "https://github.com/webetdesign/wd-cms.git"
	 }
],
```

 And add it in require section

```json
"require" : {
  ...
  "webetdesign/wd-cms": "^2.0"
  ...
},
```

## Configuration

```yaml
#config/packages/web_et_design_cms.yaml
web_et_design_cms:
  class:
    user: App\Application\Sonata\UserBundle\Entity\User
    media: App\Application\Sonata\MediaBundle\Entity\Media
  pages:
    default:
      label: 'Page par défaut'
      controller: ~
      action: ~
      template: ~
      contents:
        - { label: 'title', type: 'TEXT' }
        - { label: 'content', type: 'WYSYWYG' }

```

In class section you must define sonata entity.

In pages section you will able to register all cms page template of your project

```yaml
#config/packages/doctrine.yaml
doctrine:
    orm:
        #...
        resolve_target_entities:
            WebEtDesign\CmsBundle\Entity\CmsRouteInterface: WebEtDesign\CmsBundle\Entity\CmsRoute
```

Add CMS page context in sonata media

```yaml
#config/packages/sonata_media.yaml
sonata_media:
    contexts:
        cms_page:
            providers:
                - sonata.media.provider.dailymotion
                - sonata.media.provider.youtube
                - sonata.media.provider.image
                - sonata.media.provider.file
                - sonata.media.provider.vimeo
        
            formats:
                small: { width: 100 , quality: 70}
                big:   { width: 900 , quality: 70}
```

Configuration of ckeditor bundle

```yaml
#config/packages/fos_ck_editor.yaml
fos_ck_editor:
    configs:
        #...
        cms_page:
            toolbar:
                - [Bold, Italic, Underline, -, Cut, Copy, Paste, PasteText, PasteFromWord, -, Undo, Redo, -, BackgroundColor, TextColor, -, NumberedList, BulletedList, -, Outdent, Indent, -, JustifyLeft, JustifyCenter, JustifyRight, JustifyBlock, -, Blockquote, -, Image, Link, Unlink, Table]
                - [Format, Maximize, Source]
            
            #The following lines configure the integration of the media bundle into the wysywyg
            allowedContent: true
            filebrowserUploadMethod: form
            filebrowserBrowseRoute: admin_sonata_media_media_ckeditor_browser
            filebrowserImageBrowseRoute: admin_sonata_media_media_ckeditor_browser
            # Display images by default when clicking the image dialog browse button
            filebrowserImageBrowseRouteParameters:
                provider: sonata.media.provider.image
                context: cms_page
            # Upload file as image when sending a file from the image dialog
            filebrowserImageUploadRoute: admin_sonata_media_media_ckeditor_upload
            filebrowserImageUploadRouteParameters:
                provider: sonata.media.provider.image
                context: cms_page # Optional, to upload in a custom context
                format: big # Optional, media format or original size returned to editor
    
```

Register CMS routes

````yaml
#config/routes/cms.yaml
cms_routing:
    resource: .
    type: cms
````

Add CMS ROLE

Attention to visualize all roles in admin, your user must have the role ROLE_SUPER_ADMIN otherwise you will only see roles lower than yours.

````yaml
#config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_ADMIN_CMS:   [ROLE_ADMIN, ROLE_SONATA_ADMIN]
````

Add admin assets

```yaml
#config/packages/sonata_admin.yaml
sonata_admin:
    assets:
        extra_javascripts:
            - bundles/webetdesigncms/cms_admin.js
        extra_stylesheets:
            - bundles/webetdesigncms/cms_admin.css
```


### Full configuration example

```yaml
#config/packages/web_et_design_cms.yaml
web_et_design_cms:
    cms:
        multisite:            false
        multilingual:         false
        declination:          false
        vars:
            enable:               false
            global_service:       null
            delimiter:            DOUBLE_UNDERSCORE
    admin:
        configuration:
            class:
                content_slider:       WebEtDesign\CmsBundle\Admin\CmsContentSliderAdmin
                content:              WebEtDesign\CmsBundle\Admin\CmsContentAdmin
                menu:                 WebEtDesign\CmsBundle\Admin\CmsMenuAdmin
                page:                 WebEtDesign\CmsBundle\Admin\CmsPageAdmin
                route:                WebEtDesign\CmsBundle\Admin\CmsRouteAdmin
                site:                 WebEtDesign\CmsBundle\Admin\CmsSiteAdmin
            controller:
                content_slider:       WebEtDesign\CmsBundle\Controller\Admin\CmsContentSliderAdminController
                content:              WebEtDesign\CmsBundle\Controller\Admin\CmsContentAdminController
                menu:                 WebEtDesign\CmsBundle\Controller\Admin\CmsMenuAdminController
                page:                 WebEtDesign\CmsBundle\Controller\Admin\CmsPageAdminController
                route:                WebEtDesign\CmsBundle\Controller\Admin\CmsRouteAdminController
                site:                 WebEtDesign\CmsBundle\Controller\Admin\CmsSiteAdminController
            entity:
                content_slider:       WebEtDesign\CmsBundle\Entity\CmsContentSlider
                shared_block:         WebEtDesign\CmsBundle\Entity\CmsSharedBlock
                cms_content_has_shared_block: WebEtDesign\CmsBundle\Entity\CmsContentHasSharedBlock
                content:              WebEtDesign\CmsBundle\Entity\CmsContent
                menu:                 WebEtDesign\CmsBundle\Entity\CmsMenu
                page:                 WebEtDesign\CmsBundle\Entity\CmsPage
                route:                WebEtDesign\CmsBundle\Entity\CmsRoute
                route_interface:      WebEtDesign\CmsBundle\Entity\CmsRouteInterface
                site:                 WebEtDesign\CmsBundle\Entity\CmsSite
    class:
        user:                 ~
        media:                ~
    pages:
        # Prototype
        name:
            label:                ~
            controller:           WebEtDesign\CmsBundle\Controller\CmsController
            params:
                # Prototype
                name:
                    default:              ~
                    requirement:          ~
                    entity:               ~
                    property:             ~
            action:               index
            methods:
                # Default:
                - GET
            template:             integration/index.html.twig
            association:
                class:                ~
                queryMethod:          findAll
            contents:
                # Prototype
                -
                    code:                 ~
                    label:                ~
                    type:                 ~ # Required
            entityVars:           null
    sharedBlock:
        # Prototype
        name:
            label:                ~
            template:             ~ # Required
            contents:
                # Prototype
                -
                    code:                 ~
                    label:                ~
                    type:                 ~ # Required
    customContents:
        # Prototype
        code:
            name:                 ~ # Required
            service:              ~ # Required
```

In admin section you will able to override bundle's classes by your own for specific usage

