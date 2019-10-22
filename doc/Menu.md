# MENU

## Configuration



## Render menu

You can render menu with the following lines passing the `code` of your menu which is defined in admin.

You can pass the `parentActive` argument, it allows to make nodes containing children clickable if a link are set on it, by default is `false`.

```twig
{% set menu = knp_menu_get('cmsMenu', [], {code: 'code_of_menu', parentActive: true}) %}
{{ knp_menu_render(menu, []) }}
```

> In a multilingual context, the menus must have the same `code` between the different navigation (site). It is also necessary to pass the `page` parameter as an option, to render the menu in the correct language.

## Custom rendering 

It is possible that the rendering of your menu made by KnpMenu is not suitable for specific needs. Here is a brief example to override the template.

For an advanced configuration refer to the [KnpMenu Documentation](https://symfony.com/doc/master/bundles/KnpMenuBundle/custom_renderer.html)

Create your custom renderer template

```twig
{# template_of_custom_menu.html.twig #}
{% extends 'knp_menu.html.twig' %}

{#

	You can overide here all block contained in knp's extended file

 #}
```

Register the custum renderer template as a service


```yaml
#config/services.yaml

services:
    app.menu_renderer:
        class: Knp\Menu\Renderer\TwigRenderer
        arguments:
            - "@twig"
            - "template_of_custom_menu.html.twig"
            - "@knp_menu.matcher"
        tags:
            # The alias is what is used to retrieve the menu
            - { name: knp_menu.renderer, alias: 'name_of_your_renderer' }
```

Finally, render your menu with your custom rendering by passing it to the third parameter of the `knp_menu_render` function

```twig
{% set menu = knp_menu_get('cmsMenu', [], {code: 'code_of_menu', parentActive: true}) %}
{{ knp_menu_render(menu, [], 'name_of_your_renderer') }}
```
