<?php

namespace App\Repositories;

use App\Models\MessageAttachment;
use App\Repositories\Eloquent\BaseRepository;

class AttachmentRepository extends BaseRepository
{
    protected array $defaultWith = [
        'message',
    ];

    public function __construct(MessageAttachment $model)
    {
        parent::__construct($model);
    }

    /**
     * Create an attachment record
     *
     * @param array $data
     * @return MessageAttachment
     */
    public function create(array $data): MessageAttachment
    {
        /** @var MessageAttachment $attachment */
        $attachment = parent::create($data);
        
        return $attachment;
    }

    /**
     * Find attachment with message and conversation relationships
     * Eager loads message and conversation to avoid N+1 queries
     * Used for authorization checks (verifying participant access)
     *
     * @param int $id
     * @return MessageAttachment|null
     */
    public function findWithRelations(int $id): ?MessageAttachment
    {
        return $this->model->newQuery()
            ->with([
                'message.conversation.participants',
            ])
            ->find($id);
    }
}
