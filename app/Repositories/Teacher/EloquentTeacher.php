<?php

/**
 * @file
 * EloquentTeacher
 *
 * All code is copyright by the original authors and released under the GNU Aferro General Public License version 3 (AGPLv3) or later.
 * See COPYRIGHT and LICENSE.
 */

namespace App\Repositories\Teacher;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Teacher;

class EloquentTeacher implements TeacherInterface
{

  /**
   * Teacher
   *
   * @var App\Models\Teacher;
   *
   */
  protected $Teacher;

  /**
   * DB
   *
   * @var Illuminate\Support\Facades\DB;
   *
   */
  protected $DB;

  public function __construct(Model $Teacher, DB $DB)
  {
    $this->Teacher = $Teacher;
    $this->DB = $DB;
  }

  /**
   * Retrieve list of Teachers
   *
   * @return Illuminate\Database\Eloquent\Collection
   */
  public function searchTableRowsWithPagination($count = false, $limit = null, $offset = null, $filter = null, $sortColumn = null, $sortOrder = null)
  {
    $query = $this->DB::table('teachers AS t')
      ->select(
        't.id',
        't.carnet',
        't.name',
        't.last_name',
        't.birth_date',
        't.nit',
        't.dui',
        't.isss_number',
        't.nup_number',
        't.email',
        't.institutional_email',
        't.genre',
        't.address',
        't.status',
        't.phone_number',
        't.home_phone_number',
        't.entry_date',
        'm.id AS municipality_id',
        'm.name AS municipality',
        'd.id AS department_id',
        'd.name AS department',
        'c.id AS country_id',
        'c.name AS country',
      )
      ->join('municipalities as m', 't.municipality_id', '=', 'm.id')
      ->join('departments as d', 't.department_id', '=', 'd.id')
      ->join('countries as c', 't.country_id', '=', 'c.id')
      ->whereNull('t.deleted_at');

    if (!empty($filter)) {
      $query->where(function ($dbQuery) use ($filter) {
        foreach (['t.name', 't.carnet', 't.last_name', 't.email', 't.nit', 't.dui', 't.nup_number', 't.isss_number'] as $key => $value) {
          $dbQuery->orWhere($value, 'like', '%' . str_replace(' ', '%', $filter) . '%');
          //$dbQuery->orwhereRaw('lower(`' . $value . '`) LIKE ? ',['%' . strtolower(str_replace(' ', '%', $filter)) . '%']);
        }
      });
    }

    if (!empty($sortColumn) && !empty($sortOrder)) {
      $query->orderBy($sortColumn, $sortOrder);
    }

    if ($count) {
      return $query->count();
    }

    if (!empty($limit)) {
      $query->take($limit);
    }

    if (!empty($offset) && $offset != 0) {
      $query->skip($offset);
    }
    return new Collection(
      $query->get()
    );
  }

  /**
   * Get an Teacher by id
   *
   * @param  int $id
   *
   * @return App\Models\Teacher
   */
  public function byId($id)
  {
    return $this->Teacher->find($id);
  }
/**
   * Get an Teacher by id
   *
   * @param  int $id
   *
   * @return App\Models\Teacher
   */
  public function all()
  {
    return $this->Teacher->select(
      'id as value',
      $this->DB::raw('CONCAT(name, \' \', last_name) AS label'),
    )->get();
  }
  /**
   * Create a new Teacher
   *
   * @param array $data
   * 	An array as follows: array('field0'=>$field0, 'field1'=>$field1);
   *
   * @return App\Models\Teacher $Teacher
   */
  public function create(array $data)
  {
    $teacher = new Teacher();
    $teacher->fill($data)->save();

    return $teacher;
  }

  /**
   * Update an existing Teacher
   *
   * @param array $data
   * 	An array as follows: array('field0'=>$field0, 'field1'=>$field1);
   *
   * @param App\Models\Teacher $Teacher
   *
   * @return boolean
   */
  public function update(array $data, $teacher = null)
  {
    if (empty($teacher)) {
      $teacher = $this->byId($data['id']);
    }

    return $teacher->update($data);
  }

  /**
   * Delete existing Teacher
   *
   * @param integer $id
   * 	An Teacher id
   *
   * @return boolean
   */
  public function delete($id)
  {
    return $this->Teacher->destroy($id);
  }

  /**
   * Get next carnet
   *
   * @param integer $id
   * 	An Student id
   *
   * @return boolean
   */
  public function getNextCarnet($entryYear)
  {
    return $this->Teacher->where('entry_date', $entryYear)->count() + 1;
  }

  /**
   * Get next carnet
   *
   * @param integer $id
   * 	An Student id
   *
   * @return boolean
   */
  public function getWithoutUser()
  {
    return $this->Teacher->where('is_user_created', 0)->get();
  }
}
