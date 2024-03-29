<?php

/**
 * @file
 * EloquentSection
 *
 * All code is copyright by the original authors and released under the GNU Aferro General Public License version 3 (AGPLv3) or later.
 * See COPYRIGHT and LICENSE.
 */

namespace App\Repositories\Section;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Section;
use Illuminate\Support\Facades\Log;
class EloquentSection implements SectionInterface
{

  /**
   * Section
   *
   * @var App\Models\Section;
   *
   */
  protected $Section;

  /**
   * DB
   *
   * @var Illuminate\Support\Facades\DB;
   *
   */
  protected $DB;

  public function __construct(Model $Section, DB $DB)
  {
    $this->Section = $Section;
    $this->DB = $DB;
  }

  /**
   * Retrieve list of Sections
   *
   * @return Illuminate\Database\Eloquent\Collection
   */
  public function searchTableRowsWithPagination($count = false, $limit = null, $offset = null, $filter = null, $sortColumn = null, $sortOrder = null, $customQuery = null)
  {
    $query = $this->DB::table('sections AS s')
      ->select(
        's.id as code',
        's.code as seccion',
        's.quota',
        's.start_week',
        's.end_week',
        's.curriculum_subject_id',
        's.period_id',
        's.teacher_id',
        'm.name AS curriculum_subject_label',
        'c.name AS curriculum_label',
        'ca.name AS career_label',
        $this->DB::raw('CONCAT(t.name, \' \', t.last_name) AS teacher_name'),
      )
      ->leftJoin('teachers as t', 's.teacher_id', '=', 't.id')
      ->join('curriculum_subjects as cs', 's.curriculum_subject_id', '=', 'cs.id')
      ->join('curricula as c', 'cs.curriculum_id', '=', 'c.id')
      ->join('careers as ca', 'c.career_id', '=', 'ca.id')
      ->join('subjects as m', 'cs.subject_id', '=', 'm.id')
      ->join('periods as p', 'p.id', '=', 's.period_id')
      ->whereNull('s.deleted_at');

    if (!empty($customQuery)) {
      $query->whereNested(function ($dbQuery) use ($customQuery) {
        foreach ($customQuery as $statement) {

          if($statement['op'] == 'is not in')
          {
            $dbQuery->whereNotIn($statement['field'], explode(',',$statement['data']));
            continue;
          }

          if($statement['op'] == 'is null')
          {
            $dbQuery->whereNull($statement['field']);
            continue;
          }

          if($statement['op'] == 'is not null')
          {
            $dbQuery->whereNotNull($statement['field']);
            continue;
          }


          if($statement['field'] == 's.teacher_id'){
            if(is_null(auth()->user()->system_reference_id)){
              $dbQuery->whereNotNull($statement['field']);
              continue;
            }
            $dbQuery->where($statement['field'], $statement['op'], auth()->user()->system_reference_id);
            continue;
          }
          $dbQuery->where($statement['field'], $statement['op'], $statement['data']);

        }
      });
    }

    if (!empty($filter)) {
      $query->where(function ($dbQuery) use ($filter) {
        foreach (['m.name', 'c.name', 'ca.name'] as $key => $value) {
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
    Log::emergency($query->toSql());
    return new Collection(
      $query->get()
    );
  }

  /**
   * Get an Section by id
   *
   * @param  int $id
   *
   * @return App\Models\Section
   */
  public function byId($id)
  {
    $ids = get_keys_data($id);

    return $this->Section
      ->where('curriculum_subject_id', intval($ids[0]))
      ->where('period_id', intval($ids[1]))
      ->where('code', intval($ids[2]))
      ->whereNull('deleted_at')
      ->first();
  }
  public function byId2($id)
  {


    return $this->Section
      ->where('id', $id)
      ->whereNull('deleted_at')
      ->first();
  }
  /**
   * Get an Section by id
   *
   * @param  int $id
   *
   * @return App\Models\Section
   */
  public function countCurriculumSubjectByPeriod($curriculumSubjectId, $periodId)
  {
    return $this->Section
      ->where('curriculum_subject_id', $curriculumSubjectId)
      ->where('period_id', $periodId)
      ->whereNull('deleted_at')
      ->count();
  }

  /**
   * Get sections by period id
   *
   * @param integer $id
   *
   * @return boolean
   */
  public function getSectionsByPeriodId($periodId)
  {
    return new Collection(
      $this->DB::table('sections AS s')
        ->select(
          's.code',
          's.quota',
          's.start_week',
          's.end_week',
          's.curriculum_subject_id',
          's.period_id',
          's.teacher_id',
          'm.name AS curriculum_subject_label',
          'c.name AS curriculum_label',
          'ca.name AS career_label',
          $this->DB::raw('CONCAT(t.name, \' \', t.last_name) AS teacher_name'),

        )
        ->leftJoin('teachers as t', 's.teacher_id', '=', 't.id')

        ->join('curriculum_subjects as cs', 's.curriculum_subject_id', '=', 'cs.id')

        ->join('curricula as c', 'cs.curriculum_id', '=', 'c.id')
        ->join('careers as ca', 'c.career_id', '=', 'ca.id')
        ->join('subjects as m', 'cs.subject_id', '=', 'm.id')
        ->where('s.period_id', $periodId)
        ->whereNull('s.deleted_at')
        ->orderBy('s.code', 'asc')
        ->get()
    );
  }

    /**
   * Get sections by period id
   *
   * @param integer $id
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function byCurriculumIdAndLevel($periodId, $curriculumId, $level)
  {
    Log::emergency($this->DB::table('sections AS s')
      ->select(
        's.id as code',
        's.quota',
        's.start_week','s.end_week',
        's.curriculum_subject_id',
        's.period_id',
        's.teacher_id',
        'm.name AS curriculum_subject_label',
        'c.name AS curriculum_label',
        'ca.name AS career_label',
        'cs.cycle AS curriculum_subject_level',
        'cs.uv AS curriculum_subject_uv',
        $this->DB::raw('CONCAT(t.name, \' \', t.last_name) AS teacher_name'),

      )

      ->leftJoin('teachers as t', 's.teacher_id', '=', 't.id')

      ->join('curriculum_subjects as cs', 's.curriculum_subject_id', '=', 'cs.id')
      ->join('curricula as c', 'cs.curriculum_id', '=', 'c.id')
      ->join('careers as ca', 'c.career_id', '=', 'ca.id')
      ->join('subjects as m', 'cs.subject_id', '=', 'm.id')
      ->where('cs.curriculum_id', $curriculumId)
      ->where('cs.cycle', '<=', $level)
      ->where('s.period_id', $periodId)
      ->whereNull('s.deleted_at')
      ->orderBy('s.code', 'asc')->toSql());
    return new Collection(
      $this->DB::table('sections AS s')
        ->select(
          's.id as code',
          's.quota',
          's.start_week','s.end_week',
          's.curriculum_subject_id',
          's.period_id',
          's.teacher_id',
          'm.name AS curriculum_subject_label',
          'c.name AS curriculum_label',
          'ca.name AS career_label',
          'cs.cycle AS curriculum_subject_level',
          'cs.uv AS curriculum_subject_uv',
          $this->DB::raw('CONCAT(t.name, \' \', t.last_name) AS teacher_name'),

        )

        ->leftJoin('teachers as t', 's.teacher_id', '=', 't.id')

        ->join('curriculum_subjects as cs', 's.curriculum_subject_id', '=', 'cs.id')
        ->join('curricula as c', 'cs.curriculum_id', '=', 'c.id')
        ->join('careers as ca', 'c.career_id', '=', 'ca.id')
        ->join('subjects as m', 'cs.subject_id', '=', 'm.id')
        ->where('cs.curriculum_id', $curriculumId)
        ->where('cs.cycle', '<=', $level)
        ->where('s.period_id', $periodId)
        ->whereNull('s.deleted_at')
        ->orderBy('s.code', 'asc')
        ->get()
    );
  }


  /**
   * Create a new Section
   *
   * @param array $data
   * 	An array as follows: array('field0'=>$field0, 'field1'=>$field1);
   *
   * @return App\Models\Section $Section
   */
  public function create(array $data)
  {
    error_log("¡La base de datos de Oracle no está disponible!", 0);

    $section = new Section();
    $section->fill($data)->save();

    return $section;
  }

  /**
   * Update an existing Section
   *
   * @param array $data
   * 	An array as follows: array('field0'=>$field0, 'field1'=>$field1);
   *
   * @param App\Models\Section $Section
   *
   * @return boolean
   */
  public function update(array $data, $section = null)
  {
    if (empty($section)) {
      $section = $this->byId($data['id']);
    }

    return $section->update($data);
  }

  /**
   * Delete existing Section
   *
   * @param integer $id
   * 	An Section id
   *
   * @return boolean
   */
  public function delete($id, $section = null)
  {
    if (empty($section)) {
      $section = $this->byId($id);
    }

    return $section->delete();
  }
  /**
   * Get sections by student id
   *
   * @param integer $id
   *
   * @return boolean
   */
  public function byStudentId($periodId)
  {
    return new Collection(
      $this->DB::table('sections AS s')
        ->select(
          's.id as code',
          's.quota',
          's.curriculum_subject_id',
          's.period_id',
          's.teacher_id',
          'm.name AS curriculum_subject_label',
          'c.name AS curriculum_label',
          'ca.name AS career_label',
          $this->DB::raw('CONCAT(t.name, \' \', t.last_name) AS teacher_name'),
        )
        ->leftJoin('teachers as t', 's.teacher_id', '=', 't.id')
        ->join('curriculum_subjects as cs', 's.curriculum_subject_id', '=', 'cs.id')
        ->join('enrollments as e', 'e.code', '=', 's.id')
        ->join('curricula as c', 'cs.curriculum_id', '=', 'c.id')
        ->join('careers as ca', 'c.career_id', '=', 'ca.id')
        ->join('subjects as m', 'cs.subject_id', '=', 'm.id')
        ->where('s.period_id', $periodId)
        ->where('e.student_id', auth()->user()->system_reference_id)
        ->whereNull('s.deleted_at')
        ->orderBy('s.code', 'asc')
        ->get()
    );
  }
}
