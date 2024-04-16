<?php

namespace Storage;

use FastRoute\DataGenerator;
use Psr\Http\Message\ServerRequestInterface as Request;

use PDO;
use PDOException;
use Exception;
use DateTime;
use Entity\Project;
use Entity\Board;
use Entity\Member;
use Entity\ProjectHistory;
use Entity\UserAccount;
use Entity\UserInformation;
use Firebase\JWT\ExpiredException;
use Membership;
use Ramsey\Uuid\Uuid;

class ProjectStorage implements IProjectStorage
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

  public function insertAProject(Project $project)
  {

    try {
      $this->db->getConn()->beginTransaction();

      //Tạo một dự án;
      $query1 = "insert into projects (project_id, project_name, description, owner_id, create_at) values (?, ?, ?, ?, ?)";
      $stmt1 = $this->db->getConn()->prepare($query1);
      $stmt1->execute([$project->getProjectID(), $project->getName(), $project->getDescription(), $project->getOwnerID(), date('Y-m-d H:i:s')]);

      //tạo membership của owner và project
      $query2 = "insert into memberships values (?, ?, ?)";
      $stmt2 = $this->db->getConn()->prepare($query2);
      $stmt2->execute([$project->getOwnerID(), $project->getProjectID(), 'LEADER']);

      $query3 = "insert into project_history_member values (?,?,?,?,?,?)";
      $stmt3 = $this->db->getConn()->prepare($query3);
      $stmt3->bindValue(1, Uuid::uuid4(), PDO::PARAM_STR);
      $stmt3->bindValue(2, $project->getProjectID(), PDO::PARAM_STR);
      $stmt3->bindValue(3, $project->getOwnerID(), PDO::PARAM_STR);
      $stmt3->bindValue(4, "join");
      $stmt3->bindValue(5, null);
      $stmt3->bindValue(6, date('Y-m-d H:i:s'), PDO::PARAM_STR);
      $stmt3->execute();

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

  public function updateAProject(Project $project)
  {
    try {
      $query = 'UPDATE projects SET project_name = ?, description = ?, color = ?, status = ?, due_date = ? WHERE project_id = ?;';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindValue(1, $project->getName(), PDO::PARAM_STR);
      $stmt->bindValue(2, $project->getDescription(), PDO::PARAM_STR);
      $stmt->bindValue(3, $project->getColor(), PDO::PARAM_INT);
      $stmt->bindValue(4, $project->getStatus(), PDO::PARAM_STR);
      $stmt->bindValue(5, $project->getDueDate()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
      $stmt->bindValue(6, $project->getProjectID(), PDO::PARAM_STR);
      $stmt->execute();
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function deleteAProject(Project $project)
  {
    try {
      $query = 'DELETE FROM projects WHERE project_id = ?;';

      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindValue(1, $project->getProjectID(), PDO::PARAM_STR);
      $stmt->execute();
      $result = $stmt->rowCount();
      if ($result == 0) {
        throw new Exception("not found", 404);
      }
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function getAProject(String $projectID): Project
  {
    try {
      $query = 'select* from projects where project_id = ?';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$projectID]);

      $projectData = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$projectData) {

        throw new Exception("Project not found", 404);
      }

      $project = new Project($projectData['project_id'], $projectData['project_name'], $projectData['description'], $projectData['owner_id'], new DateTime($projectData['create_at']), $projectData['color'], $projectData['due_date']?new DateTime($projectData['due_date']):null, $projectData['status']);

      return $project;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }


  public function getAllProject(int $limit, int $offset): array
  {
    try {

      $this->db->getConn()->beginTransaction();

      $query = "select* from projects LIMIT :limit OFFSET :offset";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
      $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
      $stmt->execute();

      $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $result = [];
      foreach ($projects as $projectData) {
        $project = new Project(
          $projectData['project_id'],
          $projectData['project_name'],
          $projectData['description'],
          "",
          new DateTime($projectData['create_at'])
        );
        $result[] = $project; // thêm project vào mảng   
      };
      $this->db->getConn()->commit();
    } catch (PDOException $e) {
      $this->db->getConn()->rollBack();
      throw new Exception($e->getMessage(), 500);
    }
    return $result;
  }

  public function getProjectOfUser(string $workspace_id, string $user_id): array
  {
    try {

      $query = "select * from memberships join  projects on memberships.user_id = ? and projects.workspace_id = ? and memberships.project_id = projects.project_id";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$user_id, $workspace_id]);

      $data = $stmt->fetchAll();
      $projects = [];
      foreach ($data as $row) {
        $project = new Project($row['project_id'], $row['project_name'], $row['description'], $row['owner_id'], $row['create_at'] ? DateTime::createFromFormat('Y-m-d H:i:s', $row['create_at']) : null, $row['color'], $row['due_date']?new DateTime($row['due_date']):null, $row['status']);
        $projects[] = $project;
      }
      return $projects;
    } catch (Exception $e) {
      throw new ExpiredException($e->getMessage(), 500);
    }
  }

  public function getUserOfProject(String $project_id, int $limit, int $offset): array
  {
    try {
      $query = "select * from memberships join user_informations on memberships.user_id = user_informations.user_id where project_id = :project_id limit :limit offset :offset";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindParam(':project_id', $project_id, PDO::PARAM_STR);
      $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
      $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
      $users = [];
      $data = $stmt->fetchAll();
      foreach ($data as $row) {
        $user = new Member($row['user_id'], $row['first_name'], $row['last_name'], $row['email'], $row['role'], $row['avatar_url'], $row['color']);
        $users[] = $user;
      }

      return $users;
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function getHistoryMember(String $project_id): array
  {
    try {
      $histories = [];
      $query = "select id, pr.user_id, status, type, email, create_at, email, first_name, last_name, avatar_url, color from project_history_member pr join user_informations us on pr.project_id = ? and pr.user_id = us.user_id order by create_at DESC;";
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$project_id]);
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach($data as $row){
        $history = new ProjectHistory($row['id'], $row['project_id'], new UserInformation($row['user_id'], $row['first_name'], $row['last_name'], null, $row['avatar_url'], new UserAccount(null, $row['email'], null), $row['color']), $row['type'], $row['status'], new DateTime($row['create_at']));
        $histories[] = $history;
      }
      return $histories;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

}
