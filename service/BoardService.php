<?php

namespace Service;

use Entity\Board;
use Storage\IBoardStorage;
use Exception;

class BoardService{
  private IBoardStorage $store;

  public function __construct(IBoardStorage $store)
  {
    $this->store = $store;
  }

  public function GetBoardsOfProject(String $projectID){
    return $this->store->getBoardsOfProject($projectID);
  }

  public function  updatePreviuosBoard(String $boardID, ?String $preID, ?String $newpreviousBoardID, ?String $project_id){
    $this->store->updatePreviuosBoard($boardID, $preID, $newpreviousBoardID, $project_id);
  }
  public function deleteBoard(String $projectID){
    $this->store->deleteBoard($projectID);
  }

  public function updateBoard(?Board $board){
    $this->store->updateBoard($board);
  }

  public function addBoards(Board $board, ?String $nextBoardID): void{
   
    if($board->getName() == ""){
      throw new Exception("Board name cannot be blank", 400);
    }

    $this->store->insertBoard($board, $nextBoardID);
  }
}