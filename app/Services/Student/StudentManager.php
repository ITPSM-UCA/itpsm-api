<?php

/**
 * @file
 * Student Management Interface Implementation.
 *
 * All ModuleName code is copyright by the original authors and released under the GNU Aferro General Public License version 3 (AGPLv3) or later.
 * See COPYRIGHT and LICENSE.
 */

namespace App\Services\Student;

use App\Repositories\Student\StudentInterface;
use App\Repositories\StudentCurricula\StudentCurriculaInterface;
use App\Repositories\User\UserInterface;
use App\Repositories\Enrollment\EnrollmentInterface;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Pdf;
use Carbon\Carbon;

class StudentManager
{
  /**
   * StudentCurricula
   *
   * @var App\Repositories\StudentCurricula\StudentCurriculaInterface;
   *
   */
  protected $StudentCurricula;
  /**
   * Enrollment Manager Service
   *
   * @var App\Services\Enrollment\EnrollmentManagementInterface;
   *
   */
  protected $EnrollmentManagerService;
  /**
   * Student
   *
   * @var App\Repositories\Student\StudentInterface;
   *
   */
  protected $Student;

  /**
   * User
   *
   * @var App\Repositories\User\UserInterface;
   *
   */
  protected $User;
  /**
   * StudentCurricula
   *
   * @var App\Repositories\StudentCurricula\StudentCurriculaInterface;
   *
   */
  /**
	* Barryvdh\DomPDF\PDF
	* @var Excel
	*/
	protected $Dompdf;

  /**
   * Carbon instance
   *
   * @var Carbon\Carbon
   *
   */
  protected $Carbon;

  /**
   * responseType
   *
   * @var String
   *
   */
  protected $responseType;



  public function __construct(
    StudentCurriculaInterface $StudentCurricula,
    EnrollmentInterface $EnrollmentManagerService,
    StudentInterface $Student,
    UserInterface $User,
    PDF $Dompdf,
    Carbon $Carbon
  ) {
    $this->StudentCurricula = $StudentCurricula;
    $this->EnrollmentManagerService = $EnrollmentManagerService;
    $this->Student = $Student;
    $this->User = $User;
    $this->Dompdf = $Dompdf;
    $this->Carbon = $Carbon;
    $this->responseType = 'students';
  }

  public function getTableRowsWithPagination($request, $pager = true)
  {
    $rows = [];
    $limit = $offset = $count = $page = $totalPages = 0;
    $filter = $sortColumn = $sortOrder = '';

    if (!empty($request['filter']))
    {
      $filter = $request['filter'];
    }

    if (!empty($request['sort']) && $request['sort'][0] == '-')
    {
      $sortColumn = substr($request['sort'], 1);
      $sortOrder = 'desc';
    }
    else if (!empty($request['sort']))
    {
      $sortColumn = $request['sort'];
      $sortOrder = 'asc';
    }
    else
    {
      $sortColumn = 'id';
      $sortOrder = 'desc';
    }

    if ($pager)
    {
      $count = $this->Student->searchTableRowsWithPagination(true, $limit, $offset, $filter, $sortColumn, $sortOrder);
      encode_requested_data($request, $count, $limit, $offset, $totalPages, $page);
    }

    $this->Student->searchTableRowsWithPagination(false, $limit, $offset, $filter, $sortColumn, $sortOrder)->each(function ($student) use (&$rows) {

      $student->birth_date_with_format = !empty($student->birth_date)? $this->Carbon->createFromFormat('Y-m-d', $student->birth_date, config('app.timezone'))->format('d/m/Y') : null;
      $id = strval($student->id);
      unset($student->id);

      array_push($rows, [
        'type' => $this->responseType,
        'id' => $id,
        'attributes' => $student
      ]);
    });

    return [
      'rows' => $rows,
      'page' => $page,
      'totalPages' => $totalPages,
      'records' => $count,
    ];
  }

  public function getStudent($id)
  {
    return $this->Student->byId($id);
  }

  public function createDefaultPdf($id) {

    $data = [];
    $data['student'] = $this->getStudent($id);
    $data['periods'] = $this->EnrollmentManagerService->getperiodsforStudent($id);
    $data['rows2'] = $this->EnrollmentManagerService->getsubjectforperiodsforStudent($id);

    $data['grades'] = $this->EnrollmentManagerService->getCurriculumSubjectsEvaluatedforReport($id);
    $data['info']= $this->StudentCurricula->byId($id);
    $years=[];
    foreach ($data['periods'] as $period ){
      array_push($years, $period->year);
    }
    $data['years'] = array_unique($years);

    $data['rows']= count($data['years'])+count($data['grades']);
    return $this->Dompdf
      ->loadView('student-personal-data-pdf', $data)
      ->setPaper('letter')
      ->download('estudiante.pdf');
  }

  public function generateSystemUsers() {

    $users = [];

    $this->Student->getWithoutUser()->each(function ($student) use (&$users) {
      $generatedPassword = strtoupper(Str::random(8));

      $data = [
        'name'=> $student->name . ' ' . $student->last_name,
        'email'=> $student->institutional_email,
        'password'=> $generatedPassword,
        'system_reference_table'=> 'students',
        'system_reference_id'=> $student->id,
      ];

      $user = $this->User->create($data);
      $user->assignRole('student');
      $this->Student->update(['is_user_created' => 1], $student);

      $user->carnet = $student->carnet;
      $user->publicpassword = $generatedPassword;
      $user->password = $generatedPassword;

      array_push($users, $user);
    });

    $data = [
      'users' => $users
    ];

    return $this->Dompdf
      ->loadView('system-users-data-pdf', $data)
      ->setPaper('letter')
      ->download('UsuariosCreados.pdf');
  }

  public function create($request)
  {
    try {
      $data = $request->all();

      $carnet = $this->generateCarnet($data['last_name'], $data['entry_date'], $data['entry_period']);
      $data['carnet'] = $carnet;
      $data['institutional_email'] = $carnet . "@" . config('app.institutional_email_domain');

      $student = $this->Student->create($data);
      $id = strval($student->id);
      unset($student->id);

      return [
        'success' => true,
        'student' => $student,
        'id' => $id,
      ];
    }
    catch (\Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage(),
      ];
    }
  }

  public function update($request, $id)
  {
    $student = $this->Student->byId($id);

    if (empty($student)) {
      return [
        'success' => false,
      ];
    }

    $this->Student->update($request->all(), $student);
    $student = $this->Student->byId($id);
    unset($student->id);

    return [
      'success' => true,
      'student' => $student,
      'id' => $id,
    ];

  }

  public function delete($id)
  {
    $Student = $this->Student->byId($id);

    if (empty($Student)) {
      return false;
    }

    $this->Student->delete($id);

    return true;
  }

  private function generateCarnet($lastName, $entryYear, $entryPeriod) {
    $carnet = strtoupper(substr($lastName, 0, 2)) . str_pad($entryPeriod, 2, '0', STR_PAD_LEFT) . ($entryYear % 100) . str_pad($this->Student->getNextCarnet($entryYear), 4, '0', STR_PAD_LEFT);
    return $carnet;
  }
}
