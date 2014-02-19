<?php namespace Deiucanta\Validation;

use Illuminate\Support\Facades\Validator as Validator;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
	protected $fields = [];

	protected $rules = [];
	protected $valid = false;

	protected $errors;

	protected static $validationCache = [];

	public function __construct(array $attributes = array())
	{
		$this->parseValidationRules();

		parent::__construct($attributes);
	}

	public static function boot()
	{
		parent::boot();

		static::saving(function($model)
		{
			$model->updateUniqueValidationRules();

			return $model->validate();
		});
	}

	public function validate()
	{
		$validator = Validator::make($this->getAttributes(), $this->rules);

		if ($fails = $validator->fails()) $this->errors = $validator->messages();

		return $this->valid = !$fails;
	}

	public function errors()
	{
		return $this->errors;
	}

	protected function parseValidationRules()
	{
		if (!$this->hasValidationCache()) $this->cacheValidationRules();

		$cache = $this->getValidationCache();

		foreach ($cache as $key => $value) $this->$key = $value;
	}

	protected function cacheValidationRules()
	{
		$fields = $this->fields;
		$rules = $fillable = $guarded = [];

		foreach ($fields as $field => $properties)
		{
			if (is_string($properties)) $properties = explode('|', $properties);

			if (($key = array_search('fillable', $properties)) !== false)
			{
				$fillable[] = $field;
				unset($properties[$key]);
			}

			if (($key = array_search('guarded', $properties)) !== false)
			{
				$guarded[] = $field;
				unset($properties[$key]);
			}

			$rules[$field] = $properties;
		}

		$this->setValidationCache(compact('rules', 'fillable', 'guarded'));
	}

	protected function getValidationCache()
	{
		return static::$validationCache[get_class($this)];
	}

	protected function setValidationCache($value)
	{
		static::$validationCache[get_class($this)] = $value;
	}

	protected function hasValidationCache()
	{
		return isset(static::$validationCache[get_class($this)]);
	}

	protected function updateUniqueValidationRules()
	{
		foreach ($this->rules as $field => $rule)
		{
			if (($key = array_search('unique', $rule)) !== false)
			{
				$this->rules[$field][$key] .= ':'.$this->getTable().','.$field;

				if ($this->exists) $this->rules[$field][$key] .= ','.$this->getKey();
			}
		}
	}
}