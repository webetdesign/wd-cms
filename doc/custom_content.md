# Custom content

## Sortable collection usage

Cms doesn't have collection of medias configured add this line in `config/packages/web_et_design_cms.yaml`

```yaml
web_et_design_cms:
  customContents:
    MEDIA_COLLECTION:
      name: 'Media Collection'
      service: 'cms.custom_content.media_collection'
```
> Use the type `MEDIA_COLLECTION` in the definition of your contents.
> This will rendered a collection of medias with **cms_page context**

For more flexibilities configure your own services:

```yaml
services:
  xxxxx.custom_content.image_collection:
    class: WebEtDesign\CmsBundle\Services\CustomContents\SortableCollection
    arguments:
      - '@doctrine.orm.entity_manager'
      - App\Entity\Media #The entity, it can be actuality, document or whatever you want.
      - '@sonata.media.admin.media' #Instance of the admin of your entity
      - {'context': 'cms_page', 'provider': 'sonata.media.provider.image', 'hide_context': true, btn_delete: false} #options *
    public: true
``` 

> Options : You can pass the link_parameters options and button labels (btn_edit, btn_list, btn_delete, btn_add), set to null to hide them.
