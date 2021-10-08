# Boxes Plugin for October CMS

> **NOTE:** This plugin is not ready for production yet and just a proof of concept!

The Boxes plugin makes it easy to build partial driven layouts.

This plugin enables you to define a data schema for a partial using October's existing `yaml` configuration paradigm.
Based on that partial, an input form will be generated in the backend where an end-user can add their variables for the
partial.

A single partial is called `Box`. Boxes can be grouped into `Collections`. Collections can be placed on a CMS page to
generate it's markup.

You can have multiple `Categories` of `Collections`, like `Pages`, `Blog Entries` or `Landing Pages`.

## Installation

Install this plugin using Composer:

```bash
composer require offline/oc-boxes-plugin
```

After the installation, visit the backend settings to configure your Collection categories. By default, a `Pages`
category is created for you.

Then add a YAML file to one of your partials as described below. As soon as a YAML file is found, a dynamic backend form
will be available for any administrator to fill out.

## How it works

Let's say you have a simple partial in your theme:

```twig
{# title-text-image.htm #}

<h1>{{ box.title }}</h1>

<p>{{ box.text }}<p>

<img src="{{ box.image.getThumb(200, 'auto' }}" alt="">
```

You can now create a `yaml` file with the same name in the same folder and define the data schema:

```yaml
# title-text-image.yaml
name: Title, Text and Image
form:
    fields:
        title:
            label: Title
            type: text
            span: left
        image:
            label: Image
            type: fileupload
            span: right
        text:
            label: Inhalt
            type: richeditor
            span: full
```

## YAML Schema

The following options are available in the YAML schema:

```yaml
# full-example.yaml
modelClass: "YourVendor\\BlocksExtensionPlugin\\Models\\InstanceWithSpecialRelation"
name: Your custom partial name
eagerLoad:
    - special_relation
translatable:
    - text
    - subtitle
form:
    fields:
        title:
            label: My title input
    tabs:
        fields:
          [ ... ]
    secondaryTabs:
        fields:
          [ ... ]
```

### modelClass

Optional, use a custom instance model class for this Box. See [Custom Instance Models](#custom-instance-models).

### name

Optional, A human readable name for this partial.

### eagerLoad

Optional, defaults to `false`, defines which relations to eager load if this partial gets rendered. Possible values
are `auto` (eager load all defined relations),`false` (eager load nothing) or an array of relation names:

```yaml
eagerLoad:
    - category
    - specs_file
```

### translatable

Integration for RainLab.Translate. An array of attributes that are translatable.


```yaml
translatable:
    - title
    - content
```

### form

Required, an October CMS `form` definition. [(Docs)](https://octobercms.com/docs/backend/forms#form-fields)

## Instance Model

The plugin uses an `Instance` model to store the data for every partial. This model by default comes with four
pre-defined relations that you can use:

- attachOne: `file`, `image`
- attachMany: `files`, `images`

### Custom Instance Models

For more advanced use-cases, the default relations might not be enough. Let's say you want to use a
related `image_gallery` relation for any random plugin. To accommodate for this use-case, you have to define your
own `Instance` model that extends the default model and define your relations on that:

```php
<?php namespace YourVendor\BoxesExtension\Models;

use OFFLINE\Boxes\Models\Instance;
use AnyVendor\ImageGalleryPlugin\Models\ImageGallery;

use System\Models\File;

class InstanceWithImageGallery extends Instance
{
    public $belongsTo = [
        'image_gallery' => ImageGallery::class,
    ];
}
```

Now you can refer to this new model in any partial using the `modelClass` property in the YAML configuration. The Boxes
plugin will now always use your custom model to store data and relations.

```yaml
# any-partial.yaml
modelClass: "\\YourVendor\\BoxesExtension\\Models\\InstanceWithImageGallery"
# ...
```

```htm
{# any-partial.htm #}

<!-- This works now! -->
{% for image in box.image_gallery.images %}
    <img src="{{ image.getThumb(200, 'auto') }}" alt="">
{% endfor %}
```
