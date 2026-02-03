<?php

namespace App\DTOs;

use Spatie\Activitylog\Models\Activity;

class ActivityLogDTO extends BaseDTO
{
    public $id;
    public $description;
    public $event;
    public $subject_type;
    public $subject_id;
    public $causer_id;
    public $causer_name;
    public $properties;
    public $created_at;

    public function __construct(
        $id,
        $description,
        $event,
        $subject_type,
        $subject_id,
        $causer_id,
        $causer_name,
        $properties,
        $created_at
    ) {
        $this->id = $id;
        $this->description = $description;
        $this->event = $event;
        $this->subject_type = $subject_type;
        $this->subject_id = $subject_id;
        $this->causer_id = $causer_id;
        $this->causer_name = $causer_name;
        $this->properties = $properties;
        $this->created_at = $created_at;
    }

    public static function fromModel(Activity $activity): self
    {
        $causerName = $activity->causer ? ($activity->causer->name ?? 'N/A') : null;

        return new self(
            $activity->id,
            $activity->description,
            $activity->event,
            $activity->subject_type,
            $activity->subject_id,
            $activity->causer_id,
            $causerName,
            $activity->properties,
            $activity->created_at
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_id' => $this->causer_id,
            'causer_name' => $this->causer_name,
            'properties' => $this->properties,
            'created_at' => $this->created_at,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_name' => $this->causer_name,
            'created_at' => $this->created_at,
        ];
    }
}
