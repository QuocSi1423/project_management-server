<?php

namespace Storage;

use Entity\Board;
use Exception;
use PDO;
use PDOException;

class BoardStorage implements IBoardStorage
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

  public function insertBoard(Board $board, ?String $nextBoardID): void
  {
    try {
      $this->db->getConn()->beginTransaction();

      $query = 'INSERT INTO boards (board_id , board_name, project_id, previous_board_id) VALUES (?, ?, ?, ?);';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->bindValue(1, $board->getBoardId(), PDO::PARAM_STR);
      $stmt->bindValue(2, $board->getName(), PDO::PARAM_STR);
      $stmt->bindValue(3, $board->getProjectId(), PDO::PARAM_STR);
      $stmt->bindValue(4, $board->getPreviousBoardId(), PDO::PARAM_STR);
      $stmt->execute();

      $query = 'update boards set previous_board_id = ? where board_id = ?';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$board->getBoardId(), $nextBoardID]);

      $this->db->getConn()->commit();
    } catch (PDOException $e) {
      $this->db->getConn()->rollBack();
      if ($e->getCode() == 23000) {
        throw new Exception($e->getMessage(), 404);
      } else {
        throw new Exception($e->getMessage(), 500);
      }
    }
  }

  public function updateBoard(Board $board): void
  {
    try{
      $query = 'update boards set board_name = ? where board_id = ?';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$board->getName(), $board->getBoardId()]);
    }catch(PDOException $e){
      if ($e->getCode() == 23000) {
        throw new Exception($e->getMessage(), 404);
      } else {
        throw new Exception($e->getMessage(), 500);
      }
    }
  }

  private function updatePreviousOfBoardID($var1, $var2)
  {
    try {
      $query1 = "SELECT board_id FROM boards WHERE previous_board_id = ?";  //lấy tất cả board có cùng preID muốn đổi
      $stmt1 = $this->db->getConn()->prepare($query1);
      $stmt1->execute([$var1]);
      $boardID_result = $stmt1->fetchAll(PDO::FETCH_ASSOC);
      //duyệt qua tất cả các board có cùng previous_board cần thay đổi
      foreach ($boardID_result as $board_id) {
        $query2 = "UPDATE `boards` SET previous_board_id = ? WHERE board_id = ?"; // thay đổi preID với giá trị truyền vào
        $stmt2 = $this->db->getConn()->prepare($query2);
        $stmt2->execute([$var2, $board_id['board_id']]);
        if ($stmt2->rowCount() == 0) {
          throw new Exception("No Board Found", 404);
        }
      }
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), ($e->getCode() == 404) ? 404 : 500);
    }
  }

  public function updatePreviuosBoard(String $boardID, ?String $preID, ?String $newpreviousBoardID, ?String $project_id): void
  {
    try {
      $this->db->getConn()->beginTransaction();

      // $this->updatePreviousOfBoardID($boardID, $preID); //nối board ở phía sau của board được chỉ định vào board ở phía trước của nó. Cách TH preID(phía sau) = PreID(cần thay đổi)
      // $this->updatePreviousOfBoardID($newpreviousBoardID, $boardID); // tìm board có preID = newPreID. sau đó đặt board tìm được phía sau board chỉ định. Cách TH preID(tìm được) = ID(cần thay đổi)

      $query = "UPDATE boards SET previous_board_id = ? WHERE (previous_board_id = ?) and project_id = ?"; //thay đổi preID(cần đổi) = newPreID
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$preID, $boardID, $project_id]);

      $condition = $newpreviousBoardID == null ? "or previous_board_id is null":"";
      $query = "UPDATE boards SET previous_board_id = ? WHERE (previous_board_id = ? $condition) and project_id = ?"; //thay đổi preID(cần đổi) = newPreID
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$boardID, $newpreviousBoardID, $project_id]);

      $query = "UPDATE boards SET previous_board_id = ? WHERE board_id = ?"; //thay đổi preID(cần đổi) = newPreID
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$newpreviousBoardID, $boardID]);
      if ($stmt->rowCount() == 0) {
        throw new Exception("No Board Found", 404);
      }
      $this->db->getConn()->commit();
    } catch (Exception $e) {
      $this->db->getConn()->rollBack();
      throw new Exception($e->getMessage(), ($e->getCode() == 404) ? 404 : 500);
    }
  }

  public function getBoardsOfProject(String $projectID): array
  {
    try {
      $query = 'WITH RECURSIVE BoardList AS (SELECT board_id, board_name, project_id, previous_board_id FROM boards WHERE project_id = ? and previous_board_id IS NULL UNION ALL SELECT b.board_id, b.board_name, b.project_id, b.previous_board_id FROM boards b INNER JOIN BoardList bl ON b.previous_board_id = bl.board_id) SELECT * FROM BoardList;';
      $stmt = $this->db->getConn()->prepare($query);
      $stmt->execute([$projectID]);

      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (count($result) == 0) {
        throw new Exception("Project is not exist", 400);
      }
      $boards = array();

      foreach ($result as $row) {
        $board = new Board($row['board_id'], $row['board_name'], $projectID, $row['previous_board_id']);
        $boards[] = $board;
      }

      return $boards;
    } catch (PDOException $e) {
      throw new Exception($e->getMessage(), 500);
    }
  }

  public function deleteBoard(String $boardID): void
  {
    try {
      $this->db->getConn()->beginTransaction();

      $queryGetPreviousID = "select previous_board_id from boards where board_id = ?";
      $stmtGetPre = $this->db->getConn()->prepare($queryGetPreviousID);
      $stmtGetPre->execute([$boardID]);
      $previousBoardID = $stmtGetPre->fetch(PDO::FETCH_ASSOC)['previous_board_id'];

      $query1 = "UPDATE `boards` SET `previous_board_id`= ? WHERE previous_board_id = ?;";
      $stmt1 = $this->db->getConn()->prepare($query1);
      $stmt1->execute([$previousBoardID, $boardID]);

      $query2 = "DELETE FROM boards WHERE board_id = ?";
      $stmt2 = $this->db->getConn()->prepare($query2);
      $stmt2->execute([$boardID]);

      if ($stmt2->rowCount() == 0) { //không có dòng nào được xóa, không tìm thấy bảng
        throw new Exception("Board not found", 404);
      }
      $this->db->getConn()->commit();
    } catch (Exception $e) {
      $this->db->getConn()->rollBack();
      if ($e->getCode() == 404) {
        throw new Exception($e->getMessage(), 404);
      } else {
        $this->db->getConn()->rollBack();
        throw new Exception($e->getMessage(), 500);
      }
    }
  }
}
