Do not use this in production. This package is just a concept and is not tested yet. I need your help to get this package to be production ready.

```php
class Post extends Model
{
    protected $fields = array
	(
		'title' => 'required|fillable|unique|max:100',
		'slug'  => 'required|unique',
		'body'  => 'required|fillable'
	);

	public function setTitleAttribute($value)
	{
		$this->attributes['title'] = $value;
		$this->attributes['slug'] = Str::slug($value);
	}
}

Route::get('post/new', function()
{
	$post = Post::create(Input::all());

	return $post->valid ? $post : $post->errors();
});
```

* You can set `fillable` and `guarded` directly into the $fields property.
* You have `$post->validate()` for manual validation.
* `$post->save()` will trigger the validation.
* If validation fails the `save()` will fail too.
* `$post->errors()` will give you the error messages.

# Model / Input Validation

##  The problem

Form validation is different from model validation. I can give you an example. If you have a registration form you want the password to be between 8-32 characters. Where would you put this rule of validation. Many put this validation rule into the model which is not 100% accurate. The password will be stored in a hashed format and that basically has a fixed length. When you create a new user that validation will work, but when you update a user the validation will not work anymore. So, my idea is to keep the model validation rules simetric for create and update. The model should verify that the date is in a valid state, not the user input. Basically, the password rule will have only the "required" rule.

The form is responsible for the user input validation. This could bring a little boilerplate but the final result is much cleaner. Here are several situations where the separation of model and form validation is crucial. Without this separation thses problems (which are very common) are extremly difficult cu solve.

- You have a `Post` model. The post has 3 fields: title, slug, body. You set a mutator which fills the slug automatically. You may set the title to be unique to be unique when validating the form but this doesn't guarantee that the slug will be also unique. (eg. "First post", "First Post", "First - post" will all have "first-post" as slug)

    Also you can't set a "unique" rule for the slug because the slug is not a user input. Separating the input validation and the model validation in this situation gives you a better solution. The form validation will check for the title and the body rules and the model will check for title, body and slug too.

- You have a `Product` entry form. The main issue here is that the input will be split into different tables/models. Example: basic details go into 'Product' model, photos go into 'Image' model, tags, etc. If you validate all the input fields into the models you will have to merge the errors and then redirect back.

- Another issue is when you have fields that don't match any database fields: a confirmation checkbox. If you keep the validation into the model you can't validate the checkbox state (unless you break the rules). The model shoudn't be aware that checkbox. A form validation will work much better here.

## The solution

Validate the input data separately and validate the model data in it's final state (not input state, eg. password). Too often we think that the input data will match the model perfectly. That is not true. Sometimes you have less fields in the input, sometimes you have more fields.

This library is only the first part of what I want it to be. Is just basic model validation. Soon I will have an update for input validation which I think will be either InputValidation, FormValidation or both.