<?php
namespace Psyche\Core\Drill;
use Psyche\Core\Drill,
	Psyche\Core\Query;

/**
 * Drill ORM Relations
 * 
 * Builds one-to-one, one-to-many, belongs_to and many-to-many
 * relationships between models.
 *
 * @package Psyche\Core\Drill\Relation
 * @author Fadion Dashi
 * @version 1.0
 * @since 1.0
 */
class Relation extends Drill
{

	/**
	 * Builds one-to-one and one-to-many relationships. It initializes model
	 * object(s) based on the condition. The foreign key, if not set manually,
	 * is built from the table name and foreign key suffix.
	 * 
	 * @param string $f_table The table to be joined
	 * @param string $f_key Foreign key override if it's different from the typical $table_suffix format
	 * @param bool $many True for has_many and false for has_one
	 * @return Model
	 */
	protected function has_one_or_many ($f_table, $f_key = null, $many = false)
	{
		$f_table = strtolower($f_table);

		if (!isset($f_key))
		{
			$f_key = $this->make_foreign_key();
		}

		$class_name = static::class_name($f_table);

		$method = 'find_one';
		if ($many)
		{
			$method = 'find_many';
		}

		// Builds a call to the model with find_one or find_many.
		// It looks like: return \Psyche\Models\ModelName::find_*('f_id = id')
		return $class_name::$method("$f_key = $this->id");
	}
	
	/**
	 * Builds one-to-one relationships when the foreign key is
	 * in the actual model.
	 * 
	 * @param string $f_table
	 * @param string $f_key
	 * @return Model
	 */
	protected function belongs_to ($f_table, $f_key = null)
	{
		$f_table = strtolower($f_table);

		if (!isset($f_key))
		{
			$f_key = $this->make_foreign_key($f_table);
		}

		$class_name = static::class_name($f_table);

		return $class_name::find($this->$f_key);
	}

}