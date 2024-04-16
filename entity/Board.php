<?php
namespace Entity;

use JsonSerializable; 

class Board implements JsonSerializable{

  private ?String $board_id;
  private ?String $name;
  private ?String $project_id;
  private ?String $previous_board_id;

  public function __construct($board_id = null, $name = null, $project_id = null, $previous_board_id = null){
    $this->board_id = $board_id;
    $this->name = $name;
    $this->project_id = $project_id;
    $this->previous_board_id = $previous_board_id;
  }

  public function jsonSerialize(): mixed
  {
    return array(
      'board_id' => $this->board_id,
      'name' => $this->name,
      'project_id' => $this->project_id,
      'previous_board_id' => $this->previous_board_id
    );
  }

  public function getBoardId(): string {
      return $this->board_id;
  }

  public function setBoardId(string $board_id): void {
      $this->board_id = $board_id;
  }

  public function getName(): string {
      return $this->name;
  }

  public function setName(string $name): void {
      $this->name = $name;
  }

  public function getProjectId(): string {
      return $this->project_id;
  }

  public function setProjectId(string $project_id): void {
      $this->project_id = $project_id;
  }

  public function getPreviousBoardId(): ?string {
      return $this->previous_board_id;
  }

  public function setPreviousBoardId(string $previous_board_id): void {
      $this->previous_board_id = $previous_board_id;
  }

}
