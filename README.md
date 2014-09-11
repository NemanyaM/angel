Angel CMS
=====
Angel is a CMS built on top of Laravel.  It is available via [Packagist](https://packagist.org/packages/angel/core).

Table of Contents
-----------------
* [Try It](#try-it)
* [Installation](#installation)
* [Extending the Core](#extending-the-core)
* [Configuration](#configuration)
* [Using Slugs](#using-slugs)
* [Develop Modules](#develop-modules)

Try It
------
Check out a [live deployment of the CMS here](http://angel-test.angelvision.tv/).

Sign into the [admin section](http://angel-test.angelvision.tv/admin) with the credentials:
```
User: avadmin
Pass: password
```

Installation
------------
We are currently using Laravel 4.1 for this CMS until 4.2 is more stable.

Install Laravel 4.1 using the following command:
```bash
composer create-project laravel/laravel --prefer-dist {project-name} 4.1.*
```

Add the `angel/core` package requirement to your `composer.json` file, like this:
```javascript
"require": {
    "laravel/framework": "4.1.*",
    "angel/core": "dev-master"
},
```

Issue a `composer update` to install the package.

After the package has been installed, open `app/config/app.php` and add the following to your `providers` array:
```php
'Angel\Core\CoreServiceProvider'
```

Delete:
* All the default routes in `app/routes.php`.
* All the default filters except for the `csrf` filter in `app/filters.php`.
* All controllers in `app/controllers` except `BaseController.php`.
* All the models in `app/models`, including `User.php`.  You can replace it with a `.gitkeep` file for now to be sure to keep the `app/models` directory.

Create and configure your database so that we can run the migrations.

Finally, issue the following artisan commands:
```bash
php artisan dump-autoload                    # Dump a load
php artisan asset:publish                    # Publish the assets
php artisan config:publish angel/core        # Publish the config
php artisan migrate --package="angel/core"   # Run the migrations
```

Extending the Core
------------------
Every class in the core is easily extendable.

Let's start by extending the [PageController](https://github.com/JVMartin/angel/blob/master/src/controllers/PageController.php).

When extending this controller, you can create a method for each page URI that you've created in the administration panel.

Create the following file as `app/controllers/PageController.php`:

```php
<?php

class PageController extends \Angel\Core\PageController {

	public function home()
	{
		return 'You are home!';
	}

}
```

Remove the old binding and bind your new class at the top of your `routes.php` file:
```
App::offsetUnset('PageController');
App::singleton('PageController', function() {
	return new \PageController;
});
```

Do a `composer dump-autoload`.

Now, you should be able to navigate to `http://yoursite.com/home` and see: `You are home!`.

Configuration
-------------
Take a look at the config file you just published in `app/config/packages/angel/core/config.php`.

### Languages
The first configurations are related to languages.  By default, only one language is used and your URLs will look like this for created pages:
```
http://www.website.com/about-us
http://www.website.com/contact-us
```

If you enable multiple languages, your URLs will look like this, for instance, with English and Spanish pages:
```
http://www.website.com/en/about-us
http://www.website.com/sp/about-us
http://www.website.com/en/contact-us
http://www.website.com/sp/contact-us
```

You can then, if you choose to, easily `mod_rewrite`-out the default language base URI so that your URLs look like this:
```
http://www.website.com/about-us
http://www.website.com/sp/about-us
http://www.website.com/contact-us
http://www.website.com/sp/contact-us
```

If you would like to enable this feature, you must choose to do so before you begin development.  This is because the `languages` table is only built, and the other language-related tables (including `pages`) only have their relationships built, when this configuration is set.  This ensures that the site is optimized either way you go.

To enable this feature, first roll back all your migrations (if you've already ran them):
```bash
php artisan migrate:rollback
```

Then, set the configuration in `app/packages/angel/core/config.php`:
```php
'languages' => true
```

And finally, run the migrations so the languages table and relationships will be built:
```bash
php artisan migrate --package="angel/core"
```

### Admin URL
By default, the following configuration is set:
```
'admin_prefix' => 'admin'
```

This allows one to access the administration panel via the url `http://yoursite.com/admin`.

To be secure, you may want to change this prefix.  Hackers tend to target sites with URLs like this.

### Admin Menu
The next section is the `'menu'` array.  When you install modules, you add their indexes to this array so that they appear in the administration panel's menu.

### Menu Linkable Models
Some modules come with models that you can create menu links to in the `Menu` module.  This array is used by the `Menu Link Creation Wizard` on the `Menu` module's index.


Using Slugs
---------------------
Often times, you will want to let users access products, blog posts, news articles, etc. by name instead of by ID in the URL.

For instance: `http://yoursite.com/products/big-orange-ball`.

To do this, you want to 'sluggify' one of the columns / properties of the model.

If you are extending the [AngelModel](https://github.com/JVMartin/angel/blob/master/src/models/AngelModel.php), this is as simple as adding a `slug` column to your table with a unique index:

```php
$table->string('slug')->unique();
```

And then setting the `slugSeed` property of your model to the name of the column from which to generate the slug:
```php
protected $slugSeed = 'name';
```

Now, slugs will be automatically generated from the `name` column of the models as they are created or edited.  (You can just as easily use a `title` column or any other appropriate source.)

You can use the generated slugs after adding or editing some items.

For instance:
```php
// app/routes.php
Route::get('products/{slug}', 'ProductController@view');

// app/controllers/ProductController.php
class ProductController extends \Angel\Core\BaseController {

	public function view($slug)
	{
		$this->data['product'] = Product::where('slug', $slug)->firstOrFail();
		return View::make('products.view', $this->data);
	}
	
}
```

### Creating Unique Slugs Manually
```php
// Adding a new item:
$article        = new NewsArticle;
$article->title = Input::get('title');
$article->slug  = slug($article, 'title');
$article->save();

// Editing an item:
$article        = Article::find(1);
$article->title = Input::get('title');
$article->slug  = slug($article, 'title');
$article->save();
```

### Sluggifying a String
```php
$slug = sluggify('String to sluggify!'); // Returns 'string-to-sluggify'
```

Develop Modules
---------------
Here is where we'll put code snippets for developing modules.

### Reorderable Indexes

Assume we're developing a `persons` module package.

First, make sure that `AdminPersonsController` extends `\Angel\Core\AdminCrudController` and has the property `protected $reorderable = true;`.

```php
// persons/src/views/admin/persons/index.blade.php
@section('js')
    {{ HTML::script('packages/angel/core/js/jquery/jquery-ui.min.js') }}
    <script>
    	$(function() {
            $('tbody').sortable(sortObj);
    	});
    </script>
@stop
@section('content')
    <table class="table table-striped">
        <tbody data-url="persons/order"><!-- This data-url is appended to the admin url and posted. -->
            @foreach ($persons as $person)
                <tr data-id="{{ $person->id }}">
                    {{ Form::hidden(null, $person->order, array('class'=>'orderInput')) }}
                    <button type="button" class="btn btn-xs btn-default handle">
                        <span class="glyphicon glyphicon-resize-vertical"></span>
                    </button>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop

// persons/src/routes.php
Route::group(array('prefix' => admin_uri('persons'), 'before' => 'admin'), function() {
	Route::post('order', 'AdminPersonsController@order');
});
```
