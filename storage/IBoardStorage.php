<?php

namespace Storage;

use Entity\Board;
use Stringable;

interface IBoardStorage{
  public function insertBoard(Board $board, ?String $nextBoardID):void;
  public function updateBoard(Board $board):void;
  public function updatePreviuosBoard(String $boardID, ?String $preID, ?String $newpreviousBoardID, ?String $project_id):void;
  public function getBoardsOfProject(String $projectID):array;
  public function deleteBoard(String $boardID):void;
}