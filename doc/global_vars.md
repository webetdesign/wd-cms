# Global vars

Allow the user to use variables in cms admin e.g. seo fields or content of type text and wysywyg.

## Configuration

```twig
web_et_design_cms:
  cms:
    vars:
      enable: true
      global_service: app.cms.global_vars # See bellow
      delimiter: DOUBLE_UNDERSCORE # other : SQUARE_BRACKETS, DOUBLE_SQUARE_BRACKETS
```

### Global Service
Definition of the service 
```php
use WebEtDesign\CmsBundle\Services\AbstractCmsGlobalVars;

class CmsGlobalVars extends AbstractCmsGlobalVars
{
    // add your service you nees e.g. DoctineEntityManager
    public function __construct(){}

    public static function getAvailableVars(): array
    {
        return [
            //Déclare all global vars you need;
            myVar
        ];
    }

    public function getMyVar(): ?string
    {
        return 'foo';
    }
}
```
> Register the service
```yaml
services:
    app.cms.global_vars:
        class: App\Services\CmsGlobalVars
        public: true
```

## Use vars with your entity

Your entity must implement `WebEtDesign\CmsBundle\Entity\GlobalVarsInterface`.

```php
use WebEtDesign\CmsBundle\Entity\GlobalVarsInterface;

class MyClass implements GlobalVarsInterface
{
    ...
    
    private $myVarToExpose;

    public static function getAvailableVars(): array
    {
        return [
            'myVarToExpose',
        ];
    }
    
    public function getMyVarToExpose(): ?string
    {
        return $this->myVarToExpose;
    }
    
    ...
}
```

Then in your controller you need to register the object. 

```php
use WebEtDesign\CmsBundle\Controller\BaseCmsController;

class BrandController extends BaseCmsController
{
    public function index(Request $request, $marque)
    {
        $brand = $this->getDoctrine()->getRepository(Brand::class)->findOneBy(['slug' => $marque]);

        $this->setVarsObject($brand);

        return $this->defaultRender([
            'brand' => $brand,
        ]);
    }
}
```
> Your controller must extend from `WebEtDesign\CmsBundle\Controller\BaseCmsController`

To find all the variables available in admin, you must set the entityVars attribute in the page template configuration.

```yaml
web_et_design_cms:
  pages:
    brand:
      label: 'Page marque'
      controller: App\Controller\BrandController
      action: index
      template: brand/index.html.twig
      params:
        marque:
          default: null
          requirement: null
          entity: App\Entity\Product\Brand
          property: slug
      contents:
        - { code: 'title', type: 'TEXT' }
        - { code: 'title_middle', label: 'title_middle', type: 'TEXT' }
        - { code: 'content', type: 'WYSYWYG' }
      entityVars: App\Entity\Product\Brand
```
