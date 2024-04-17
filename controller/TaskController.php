<?php

namespace Controller;

use Entity\Task;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Service\TaskService;
use Ramsey\Uuid\Uuid;
use Exception;
use DateTime;

class TaskController
{
  private TaskService $service;
  public function __construct(TaskService $service)
  {
    $this->service = $service;
  }
  public function AddATask(Request $req, Response $res)
  {
    $body = $req->getBody()->getContents();
    $data = json_decode($body);
    if (!isset($data->project_id)) {
      $res = $res->withStatus(400);
      $res->getBody()->write("Project ID là required");
      return $res;
    }
    if (!isset($data->task_id)) {
      $data->task_id = Uuid::uuid4();
    }
    if (!isset($data->task_name)) {
      $res = $res->withStatus(400);
      $res->getBody()->write("Task name is required");
      return $res;
    }
    if (!isset($data->assignedUserID)) {
      $res = $res->withStatus(400);
      $res->getBody()->write("assigned User ID is required");
      return $res;
    }
    if (!isset($data->boardID)) {
      $res = $res->withStatus(400);
      $res->getBody()->write("board ID is required");
      return $res;
    }
    $task = new Task($data->task_id, $data->task_name, $data->project_id, $data->assignedUserID, $data->boardID, DateTime::createFromFormat('Y-m-d H:i:s', $data->create_at));
    try {
      $this->service->AddATask($task);
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(
        array(
          "message" => "create successfully",
          "Task" => $task
        )
      ));
      return $res;
    } catch (Exception $e) {
      if ($e->getCode() == 409) {
        $res = $res->withStatus(409);
        $res->getBody()->write($e->getMessage());
      } else {
        $res = $res->withStatus(500);
        $res->getBody()->write($e->getMessage());
      }
      return $res;
    }
  }
  public function updateTask(Request $req, Response $res)
  {
    try {
      $body = $req->getBody()->getContents();
      $data = json_decode($body);

      if ($data && !empty((array) $data)) {
        // Kiểm tra và gán giá trị null cho các trường không được chỉ định
        $data->task_name = $data->task_name ?? null;
        $data->description = $data->description ?? null;
        $data->project_id = $data->project_id ?? null;
        $data->assigned_user = $data->assigned_user ?? null;
        $data->board_id = $data->board_id ?? null;
        $data->start_date = isset($data->start_date) ? new DateTime($data->start_date) : null;
        $data->end_date = isset($data->end_date) ? new DateTime($data->end_date) : null;
        $data->status = $data->status ?? null;
        // Tạo một đối tượng Task từ dữ liệu nhận được
        $task = new Task(
          $data->task_id,
          $data->task_name,
          $data->description,
          $data->project_id,
          $data->assigned_user,
          $data->board_id,
          $data->start_date,
          $data->end_date,
          $data->status
        );

        $this->service->updateTask($task);

        // Trả về kết quả thành công
        $res = $res->withStatus(200);
        $res->getBody()->write(json_encode( array("message" => "Update successfully", "task"=>$task)));
      } else {
        throw new Exception("Empty request data", 400);
      }
    } catch (Exception $e) {
      $status = $e->getCode() ?: 500;
      $res = $res->withStatus($status);
      $res->getBody()->write(json_encode(["error" => $e->getMessage()]));
    }

    return $res;
  }


  public function updateStatus(Request $req, Response $res)
  {
    try {

      $requestBody = $req->getBody()->getContents();
      $requestBody = json_decode($requestBody, true);
      if (!isset($requestBody['board_id'])) {
        throw new Exception("Board ID is required", 400);
      }
      $boardID = $requestBody['board_id'];
      $taskID = $req->getAttribute('task_id');
      $projectID = $req->getAttribute('project_id');

      $task = new Task($taskID, null, null, $projectID, null, $boardID, null);
      $this->service->updateStatus($boardID, $taskID);

      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(
        array(
          "Status updated successfully",
          "taskID"  => $taskID,
          "boardID" => $boardID
        )
      ));
      return $res;
    } catch (Exception $e) {
      if ($e->getCode() == 400) {
        $res = $res->withStatus(400);
        $res->getBody()->write($e->getMessage());
      } else {
        $res = $res->withStatus(500);
        $res->getBody()->write($e->getMessage());
      }
      return $res;
    }
  }
  public function updateAssignedUSer(Request $req, Response $res)
  {
    try {

      $requestBody = $req->getBody()->getContents();
      $requestBody = json_decode($requestBody, true);
      if (!isset($requestBody['assigned_user_id'])) {
        throw new Exception("Assigned User ID is required", 400);
      }
      $assignedUserID = $requestBody['assigned_user_id'];
      $taskID = $req->getAttribute('task_id');
      $projectID = $req->getAttribute('project_id');

      $task = new Task($taskID, null, $projectID, $assignedUserID, null, null);
      $this->service->updateAssignedUSer($assignedUserID, $taskID);

      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(
        array(
          "Assigned UserID updated successfully",
          "taskID"  => $taskID,
          "AsigneduserID" => $assignedUserID
        )
      ));
      return $res;
    } catch (Exception $e) {
      if ($e->getCode() == 404) {
        $res = $res->withStatus(404);
        $res->getBody()->write($e->getMessage());
      } else {
        $res = $res->withStatus(500);
        $res->getBody()->write($e->getMessage());
      }
      return $res;
    }
  }

  public function GetTasksOfProject(Request $req, Response $res)
  {
    try {
      $project_id = $req->getAttribute('project_id');
      $queryParams = $req->getQueryParams();
      $status = $queryParams['staus'];
      $assigneeID = $queryParams['assignee_id'];
      $startDate = null;
      $endDate = null;

      if (isset($queryParams['start_date'])) {
        $startDate = new DateTime($queryParams['start_date']);
      }

      if (isset($queryParams['end_date'])) {
        $endDate = new DateTime($queryParams['end_date']);
      }

      $tasks = $this->service->getTaskOfProject($project_id, $status, $assigneeID, $startDate, $endDate);
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode($tasks));
      return $res;
    } catch (Exception $e) {
      $res = $res->withStatus(500);
      $res->getBody()->write($e->getMessage());
      return $res;
    }
  }
}
