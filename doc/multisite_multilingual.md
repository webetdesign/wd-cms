# Multisite / Multilingual

## Configuration

For start a Multisite / Multilingual app you must configure your **site** in backoffice. 

Add the lines in bundle configuration

```yml 
web_et_design_cms:
  cms:
    multisite: true
    multilingual: true
```

> Multisite can be activate alone but multilingual need multisite.

### Attributes

| Name          | Description   | Exemple |
| ------------- |---------------| --------|
| label         | Name          |         |
| locale        | Locale        | fr, en, de |
| host          | Domaine       | monsite1.fr, monsite2.fr |
| Host Multilingual | In a multilingual context, checking this box allows you to manage the language with the domain extension without prefixing the route | without prefix: monsite.fr <br/> with prefix: monsite.fr/fr |
| Default       | You must set one default site. |
| Flag Icon     | Code of the flage icon | fr => ![fr](https://www.countryflags.io/fr/flat/16.png)|


### Locale Switcher

In Multilingual configuration you can use in your template this twig method `cms_render_locale_switch` to render the links of sibling pages associated with other language

> You may have to overide this template for advanced use or style.
> `@WebEtDesignCms/block/cms_locale_switch.html.twig`
