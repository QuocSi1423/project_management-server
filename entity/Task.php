<?php
namespace Entity;

use DateTime;
use JsonSerializable;

class Task implements JsonSerializable
{
    private ?string $taskID;
    private ?string $name;
    private ?string $description;
    private ?string $projectID;
    private ?UserInformation $assignedUser; // Sửa ở đây
    private ?string $boardID;
    private ?DateTime $startDate;
    private ?DateTime $endDate;
    private ?int $status;

    public function __construct(
        ?string $taskID = null,
        ?string $name = null,
        ?string $description = null,
        ?string $projectID = null,
        ?UserInformation $assignedUser = null, // Sửa ở đây
        ?string $boardID = null,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
        ?int $status = null
    ) {
        $this->taskID = $taskID;
        $this->name = $name;
        $this->description = $description;
        $this->projectID = $projectID;
        $this->assignedUser = $assignedUser; // Sửa ở đây
        $this->boardID = $boardID;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [$this->taskID, $this->name, $this->projectID, $this->assignedUser, $this->boardID, $this->startDate->format('Y-m-d H:i:s')];
    }

    // Getters
    public function getTaskID(): ?string
    {
        return $this->taskID;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getProjectID(): ?string
    {
        return $this->projectID;
    }

    public function getAssignedUser(): ?UserInformation // Sửa ở đây
    {
        return $this->assignedUser;
    }

    public function getBoardID(): ?string
    {
        return $this->boardID;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    // Setters
    public function setTaskID(?string $taskID): void
    {
        $this->taskID = $taskID;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setProjectID(?string $projectID): void
    {
        $this->projectID = $projectID;
    }

    public function setAssignedUser(?UserInformation $assignedUser): void // Sửa ở đây
    {
        $this->assignedUser = $assignedUser;
    }

    public function setBoardID(?string $boardID): void
    {
        $this->boardID = $boardID;
    }

    public function setStartDate(?DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function setEndDate(?DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    // JsonSerializable implementation
    public function jsonSerialize()
    {
        return array(
            'task_id' => $this->taskID,
            'task_name' => $this->name,
            'project_id' => $this->projectID,
            'assigned_user' => $this->assignedUser, // Sửa ở đây
            'board_id' => $this->boardID,
            'start_date' => $this->startDate ? $this->startDate->format('Y-m-d') : null,
            'end_date' => $this->endDate ? $this->endDate->format('Y-m-d') : null,
            'status' => $this->status
        );
    }
}