<?php

namespace Service;

use DateTime;
use Exception;

use Storage\ITaskStorage;
use Entity\Task;

class TaskService{
  private ITaskStorage $store;

  public function __construct(ITaskStorage $store)
  {
    $this->store = $store;
  }
  public function AddATask(Task $task):void
  {
    if($task->getTaskID() == ""){
      throw new Exception("Task id cannot be blank", 400);
    }
    if($task->getName() == ""){
      throw new Exception("Task name cannot be blank", 400);
    }
    if($task->getProjectId() == ""){
    throw new Exception("Project id cannot be blank", 400);
    }

    $this->store->inserTask($task);
  }
  public function updateStatus(String $boardID, String $taskID):void{
    
    $this->store->updateStatus($boardID,$taskID);
  }
  public function updateAssignedUSer(String $assignedUserID, String $taskID):void{
    $this->store->updateAssignedUSer($assignedUserID,$taskID);
  }
  public function updateTask(Task $task):void{
    $this->store->updateTask($task);
  }

  public function getTaskOfProject(?String $project_id, ?int $status, ?String $assigneeID, ?DateTime $startDate, ?DateTime $endDate ):array{
    return $this->store->getTasksOfProject($project_id, $status, $assigneeID, $startDate, $endDate);
  }
}
