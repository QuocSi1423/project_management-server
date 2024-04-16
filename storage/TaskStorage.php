<?php

namespace Storage;

use Entity\Board;
use PDO;
use PDOException;
use Exception;
use DateTime;

use Storage\ITaskStorage;
use Entity\Task;
use Entity\UserAccount;
use Entity\UserInformation;

class TaskStorage implements ITaskStorage
{
  private PDOManager $db;

  public function __construct(PDOManager $db = null)
  {

    if (isset($db)) {
      $this->db = $db;
    } else {
      $this->db = new PDOManager(null);
    }
  }

  public function inserTask(Task $task): void
  {
    try {
      $this->db->getConn()->beginTransaction();

      //Tạo một công viêc;
      $query = "INSERT INTO tasks (task_id,task_name,project_id,assigned_user_id,board_id,create_at) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute($task->toArray());

      $this->db->getConn()->commit();
    } catch (PDOException $e) {
      $this->db->getConn()->rollBack();

      if ($e->errorInfo[1] == 1062) {
        throw new Exception($e->getMessage(), 409);
      } else {
        throw new Exception($e->getMessage(), 500);
      }
    }
  }

  public function updateTask(Task $task): void
  {
    try {
      $query = 'UPDATE tasks SET task_name = ?, description = ? WHERE task_id = ?;';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindValue(1, $task->getName(), PDO::PARAM_STR);
      $stmt->bindValue(2, $task->getDescription(), PDO::PARAM_STR);
      $stmt->bindValue(3, $task->getTaskID(), PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->rowCount();
      if ($result == 0) {
        throw new Exception("No Task Found", 404);
      }
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function updateStatus(String $boardID, String $taskID): void
  {
    try {

      $query = 'UPDATE tasks SET board_id = ? WHERE task_id = ?;';
      $stmt = $this->db->getConn()->prepare($query);

      $stmt->execute([$boardID, $taskID]);
      $result = $stmt->rowCount();
      if ($result == 0) {
        throw new Exception("No Task Found", 404);
      }
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function updateAssignedUSer(String $assignedUserID, String $taskID): void
  {
    try {

      $query = 'UPDATE tasks SET assigned_user_id = ? WHERE task_id = ?;';
      $stmt = $this->db->getConn()->prepare($query);

      $stmt->execute([$assignedUserID, $taskID]);
      $result = $stmt->rowCount();
      if ($result == 0) {
        throw new Exception("No Task Found", 404);
      }
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function getTasksOfProject(?String $projectID, ?int $status, ?String $assigneeID, ?DateTime $startDate, ?DateTime $endDate): array
  {
    try {
      $tasks = [];
      $query = "select * from tasks t left join user_informations u on t.assigned_user_id = u.user_id where project_id = ? and (? is null or status = ?) and (? is null or assigned_user_id = ?) and (? is null or ? <= due_date) and (? is null or ? >= due_date)";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindValue(1, $projectID, PDO::PARAM_STR);
      $stmt->bindValue(2, $status, PDO::PARAM_INT);
      $stmt->bindValue(3, $status, PDO::PARAM_INT);
      $stmt->bindValue(4, $assigneeID, PDO::PARAM_STR);
      $stmt->bindValue(5, $assigneeID, PDO::PARAM_STR);
      $stmt->bindValue(5, $assigneeID, PDO::PARAM_STR);
      $stmt->bindValue(6, $startDate ? $startDate->format('Y-m-d H:i:s') : null, PDO::PARAM_STR);
      $stmt->bindValue(7, $startDate ? $startDate->format('Y-m-d H:i:s') : null, PDO::PARAM_STR);
      $stmt->bindValue(8, $endDate ? $endDate->format('Y-m-d H:i:s') : null, PDO::PARAM_STR);
      $stmt->bindValue(9, $endDate ? $endDate->format('Y-m-d H:i:s') : null, PDO::PARAM_STR);
      $stmt->execute();
      $data = $stmt->fetchAll();
      foreach ($data as $row) {
        $task = new Task();
        $task->setTaskID($row['task_id']);
        $task->setName($row['task_name']);
        $task->setDescription($row['description']);
        $task->setProjectID($row['project_id']);
        $user = new UserInformation();
        $user->setFirstName($row['first_name']);
        $user->setLastName($row['last_name']);
        $user->setUserID($row['user_id']);
        $user->setColor($row['color']);
        $user->setAvatarURL($row['avatar_url']);
        $user->setUserAccount(new UserAccount(null, $row['email'], null));
        $task->setAssignedUser($user);
        $task->setBoardID($row['board_id']);
        $task->setStartDate(new DateTime($row['start_date']));
        $task->setEndDate($row['due_date'] == null ? null : new DateTime($row['due_date']));
        $task->setStatus($row['status']);

        $tasks[] = $task;
      }
      return $tasks;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function deleteTask(String $taskID): void
  {
  }
}
