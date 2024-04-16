<?php

namespace Controller;

use Entity\Board;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Service\BoardService;
use Exception;
use Firebase\JWT\ExpiredException;
use LDAP\Result;
use Ramsey\Uuid\Nonstandard\Uuid;

class BoardController
{
  private BoardService $service;
  public function __construct(BoardService $service)
  {
    $this->service = $service;
  }
  public function GetBoads(Request $req, Response $res)
  {
    try {
      $boards = $this->service->GetBoardsOfProject($req->getAttribute('project_id'));
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode($boards));
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

  public function changeWorkflow(Request $req, Response $res)
  {
    $body = $req->getBody()->getContents();
    $data = json_decode($body);
    try {
      $this->service->updatePreviuosBoard($data->board_id, $data->previous_board_id, $data->new_previous_id, $req->getAttribute('project_id'));
      $boards = new Board($data->board_id, $data->name, $data->project_id, $data->new_previous_id);

      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(array(
        "message" => "update successfully",
        "project" => $boards
      )));
    } catch (Exception $e) {
      if ($e->getCode() == 404) {
        $res = $res->withStatus(404);
      } else $res = $res->withStatus(500);
      $res->getBody()->write($e->getMessage());
    }
    return $res;
  }

  public function deleteBoard(Request $req, Response $res)
  {
    try {
      $this->service->deleteBoard($req->getAttribute('board_id'));
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(
        array("message" => "delete successfully")
      ));
    } catch (Exception $e) {
      if ($e->getCode() == 404) {
        $res = $res->withStatus(404);
        $res->getBody()->write($e->getMessage());
      } else {
        $res = $res->withStatus(500);
        $res->getBody()->write($e->getMessage());
      }
    }
    return $res;
  }

  public function addBoards(Request $req, Response $res)
  {
    try {
      $body = $req->getBody()->getContents();
      $data = json_decode($body);
      if (!isset($data->previous_board_id)) {
        $data->previous_board_id = null;
      }

      if (!isset($data->board_name)) {
        throw new Exception("Name is required", 400);
      }

      if (!isset($data->next_board_id)) {
        $data->next_board_id = null;
      }

      $projectID = $req->getAttribute('project_id');
      $boardID = Uuid::uuid4();
      $board = new Board($boardID, $data->board_name, $projectID, $data->previous_board_id);
      $this->service->addBoards($board, $data->next_board_id);
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode(
        array(
          "message" => "Successfully created",
          "board" => $board
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

  public function updateBoard(Request $req, Response $res){
    try{
      $body = $req->getBody()->getContents();
      $data = json_decode($body);

      if(!isset($data->board_name)){
        throw new Exception("Name is required", 400);
      }

      $board = new Board($req->getAttribute('board_id'), $data->board_name, null, null);
      $this->service->updateBoard($board);

      $res->withStatus(200);
      $res->getBody()->write("successfully updated");
      return $res;
    }catch(Exception $e){
      $res = $res->withStatus($e->getCode());
      $res->getBody()->write($e->getMessage());
      return $res;
    }
  }
}
