# PAGE
## Create a new page

Edit the `pages` section in `config/packages/web_et_design_cms.yaml`

### Example of creating a FAQ page

```yaml
web_et_design_cms:
  pages:
    faq:
      label: 'FAQ'
      controller: App\Controller\FaqController
      action: index
      template: faq.html.twig
      contents:
        - { code: 'title', type: 'TEXT' }
        - { code: 'content', type: 'WYSYWYG' }     
```

If your route needs settings, you can define it here too. In the example below we define the page parameter with these requirements and default value. The path will be automatically generated with the parameters: `page_name/{page}` in our case

```yaml
web_et_design_cms:
  pages:
    a_page:
      label: 'exemple'
      controller: App\Controller\ExempleController
      action: index
      template: exemple.html.twig
      contents:
        - { code: 'title', type: 'TEXT' }
        - { code: 'content', type: 'WYSYWYG' }
        - { code: 'tech_name', label: 'fancy_name', type: 'TEXT' }
      params:
        page: 
          requirement: /\d+/
          default: 1
```

The content section offers you the possibility to preset variables that are in your template. 

The authorized types are `'TEXT','TEXTAREA','WYSYWYG','MEDIA','SLIDER'`.

### Example of custom controller

Custom controllers must extend from `WebEtDesign\CmsBundle\Controller\BaseCmsController`

```php 
<?php

namespace App\Controller;

use WebEtDesign\CmsBundle\Controller\BaseCmsController;

class exempleController extends BaseCmsController
{
    public function index()
    {
        //do custom stuf here;
   
        return $this->defaultRender([
            //add custom vars here;
        ]);
    }
}

```


## Rendering contents in your page

### TEXT, TEXTAREA, WYSYWYG
```twig
{% cms_render_content(page, 'code_of_content') %}
```

### MEDIA
```twig
{% media cms_media(page, 'code_of_content'), 'format' %}
{# 
	format : Is defined in sonata media configuration in cms_page context
	By default : small, big or reference for source image
	See sonata documentation for more
	https://sonata-project.org/bundles/media/master/doc/reference/installation.html
#}
```

### SLIDERS 

The slider type looks like the media type except that the cms_slider function returns a collection of media. You should loop on the collection to render them.

```twig
{% set slides = cms_sliders(page, 'sliders') %}
{% for slide in slides %}
    {% media slide.media, 'format' %}
{% endfor %}
```

## SMO

The **CmsPage class** use **WebEtDesign\CmsBundle\Utils\SmoTwitterTrait.php** and **WebEtDesign\CmsBundle\Utils\SmoFacebookTrait.php**.

For rendering them use this partial:

```twig
{% block metaSMO %}
    {% include './partials/metaSMO.html.twig' with {object: page} %}
{% endblock %}
```

> You can use this traits in your entity and overide the metaSMO block passing your entity instead of the page.
