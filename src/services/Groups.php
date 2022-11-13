<?php

namespace venveo\redirect\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use Throwable;
use venveo\redirect\models\Group;
use venveo\redirect\records\Group as GroupRecord;
use yii\base\Component;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;

/**
 *
 * @property-read Group[] $allGroups
 * @property string $name
 * @property string $description
 */
class Groups extends Component
{
    /**
     * @var MemoizableArray<Group>|null
     * @see _groups()
     */
    private ?MemoizableArray $_groups = null;

    /**
     * Serializer
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_groups']);
        return $vars;
    }

    /**
     * Returns a memoizable array of all groups.
     *
     * @return MemoizableArray<Group>
     */
    private function _groups(): MemoizableArray
    {
        if (!isset($this->_groups)) {
            $groups = [];

            /** @var GroupRecord[] $groupRecords */
            $groupRecords = GroupRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($groupRecords as $groupRecord) {
                $groups[] = $this->_createGroupFromRecord($groupRecord);
            }

            $this->_groups = new MemoizableArray($groups);
        }

        return $this->_groups;
    }


    /**
     * Returns all category groups.
     *
     * @return Group[]
     */
    public function getAllGroups(): array
    {
        return $this->_groups()->all();
    }


    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return Group|null
     */
    public function getGroupById(int $groupId): ?Group
    {
        return $this->_groups()->firstWhere('id', $groupId);
    }

    /**
     * Returns a group by its UID.
     *
     * @param string $uid
     * @return Group|null
     * @since 3.1.0
     */
    public function getGroupByUid(string $uid): ?Group
    {
        return $this->_groups()->firstWhere('uid', $uid, true);
    }


    /**
     * @param int $id
     * @return bool
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function deleteGroupById(int $id): bool
    {
        $groupRecord = GroupRecord::findOne($id);

        if (!$groupRecord) {
            return false;
        }

        return (bool)$groupRecord->delete();
    }

    /**
     * @param Group $group
     * @param bool $runValidation
     * @return bool
     * @throws BadRequestHttpException
     */
    public function saveGroup(Group $group, bool $runValidation = true): bool
    {
        if ($group->id) {
            $record = GroupRecord::findOne($group->id);

            if (!$record) {
                throw new BadRequestHttpException("Invalid group ID: $group->id");
            }
        } else {
            $record = new GroupRecord();
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('Group not saved due to validation error.', __METHOD__);

            return false;
        }

        $record->name = $group->name;
        $record->description = $group->description;

        $record->save(false);

        // Now that we have a record ID, save it on the model
        $group->id = $record->id;

        $this->clearCache();

        return true;
    }

    /**
     * @return void
     */
    protected function clearCache(): void
    {
        // Clear caches
        $this->_groups = null;
    }

    /**
     * Returns a Query object prepped for retrieving Groups.
     *
     * @return Query The query object.
     */
    private function _createGroupsQuery(): Query
    {
        return (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.description',
            ])
            ->from(['{{%venveo_redirect_groups}}' . ' groups']);
    }

    /**
     * Creates a Group with attributes from a GroupRecord.
     *
     * @param GroupRecord|null $groupRecord
     * @return Group|null
     */
    private function _createGroupFromRecord(?GroupRecord $groupRecord = null): ?Group
    {
        if (!$groupRecord) {
            return null;
        }

        $group = new Group($groupRecord->toArray([
            'id',
            'name',
            'description',
            'uid',
        ]));

        return $group;
    }

    /**
     * Gets a group's record by uid.
     *
     * @param string $uid
     * @return GroupRecord
     */
    private function _getGroupRecord(string $uid): GroupRecord
    {
        $query = GroupRecord::find();
        $query->andWhere(['uid' => $uid]);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var GroupRecord */
        return $query->one() ?? new GroupRecord();
    }
}
