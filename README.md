# wd-cms

## Requirement
- PHP ^7
- symfony ^3.4 | ^4
- sonata admin and media bundle

## Installation
Add the repo to your composer.json
``` json
 "repositories": [
     {
       "type": "git",
       "url": "https://github.com/webetdesign/wd-cms.git"
     }
   ],
 ```
 And add it in require section
 ``` json
"require" : {
  ...
  "webetdesign/wd-cms": "^1.0"
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

In class you must define sonata entity
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
    class:
        user: App\Application\Sonata\UserBundle\Entity\User
        media: App\Application\Sonata\MediaBundle\Entity\Media
    admin:
        configuration:
            class:
                content_slider: WebEtDesign\CmsBundle\Admin\CmsContentSliderAdmin
                content: WebEtDesign\CmsBundle\Admin\CmsContentAdmin
                menu: WebEtDesign\CmsBundle\Admin\CmsMenuAdmin
                page: WebEtDesign\CmsBundle\Admin\CmsPageAdmin
                route: WebEtDesign\CmsBundle\Admin\CmsRouteAdmin
            controller:
                content_slider: WebEtDesign\CmsBundle\Controller\Admin\CmsContentSliderAdminController
                content: WebEtDesign\CmsBundle\Controller\Admin\CmsContentAdminController
                menu: WebEtDesign\CmsBundle\Controller\Admin\CmsMenuAdminController
                page: WebEtDesign\CmsBundle\Controller\Admin\CmsPageAdminController
                route: WebEtDesign\CmsBundle\Controller\Admin\CmsRouteAdminController
            entity:
                content_slider: WebEtDesign\CmsBundle\Entity\CmsContentSlider
                content: WebEtDesign\CmsBundle\Entity\CmsContent
                menu: WebEtDesign\CmsBundle\Entity\CmsMenu
                page: WebEtDesign\CmsBundle\Entity\CmsPage
                route: WebEtDesign\CmsBundle\Entity\CmsRoute
    pages:
        default:
            label: 'Page par défaut'
            controller: null
            action: null
            template: a_twig_template.html.twig
            contents:
                -
                    label: title
                    type: TEXT
                -
                    label: content
                    type: WYSYWYG
            params: {  }
```

In admin section you will able to override bundle's classes by your own for specific usage

