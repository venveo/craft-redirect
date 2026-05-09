<?php

namespace venveo\redirect\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m260509_190359_dedupe_catch_all_unique_index migration.
 */
class m260509_190359_dedupe_catch_all_unique_index extends Migration
{
    private const TABLE = '{{%venveo_redirects_catch_all_urls}}';
    private const UNIQUE_COLUMNS = ['siteId', 'uri', 'query'];

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(self::TABLE)) {
            return true;
        }

        $primarySiteId = (new Query())
            ->select(['id'])
            ->from('{{%sites}}')
            ->orderBy([
                'primary' => SORT_DESC,
                'sortOrder' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->scalar($this->db);

        if (!$primarySiteId) {
            echo "Unable to normalize catch-all rows: no site exists.\n";
            return false;
        }

        $this->normalizeRows((int)$primarySiteId);
        $this->deduplicateRows();

        $this->alterColumn(self::TABLE, 'uri', $this->string(255)->notNull()->defaultValue(''));
        $this->alterColumn(self::TABLE, 'query', $this->string(255)->notNull()->defaultValue(''));
        $this->alterColumn(self::TABLE, 'siteId', $this->integer()->notNull());

        if (!$this->uniqueIndexExists()) {
            $this->createIndex(null, self::TABLE, self::UNIQUE_COLUMNS, true);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m260509_190359_dedupe_catch_all_unique_index cannot be reverted.\n";
        return false;
    }

    private function normalizeRows(int $primarySiteId): void
    {
        $this->update(self::TABLE, ['siteId' => $primarySiteId], ['siteId' => null]);
        $this->update(self::TABLE, ['uri' => ''], ['uri' => null]);
        $this->update(self::TABLE, ['query' => ''], ['query' => null]);

        $this->execute('UPDATE {{%venveo_redirects_catch_all_urls}} SET [[uri]] = SUBSTRING([[uri]], 1, 255) WHERE CHAR_LENGTH([[uri]]) > 255');
        $this->execute('UPDATE {{%venveo_redirects_catch_all_urls}} SET [[query]] = SUBSTRING([[query]], 1, 255) WHERE CHAR_LENGTH([[query]]) > 255');
    }

    private function deduplicateRows(): void
    {
        $rows = (new Query())
            ->select(['id', 'siteId', 'uri', 'query', 'hitCount', 'ignored', 'referrer', 'dateCreated', 'dateUpdated'])
            ->from(self::TABLE)
            ->orderBy([
                'siteId' => SORT_ASC,
                'uri' => SORT_ASC,
                'query' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all($this->db);

        $groups = [];
        foreach ($rows as $row) {
            $key = implode("\x1F", [
                (string)$row['siteId'],
                (string)$row['uri'],
                (string)($row['query'] ?? ''),
            ]);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'id' => (int)$row['id'],
                    'duplicateIds' => [],
                    'hitCount' => (int)$row['hitCount'],
                    'ignored' => $this->isTruthy($row['ignored']),
                    'referrer' => $row['referrer'] ?: null,
                    'referrerDate' => $row['referrer'] ? $row['dateUpdated'] : null,
                    'dateCreated' => $row['dateCreated'],
                    'dateUpdated' => $row['dateUpdated'],
                ];
                continue;
            }

            $groups[$key]['duplicateIds'][] = (int)$row['id'];
            $groups[$key]['hitCount'] += (int)$row['hitCount'];
            $groups[$key]['ignored'] = $groups[$key]['ignored'] || $this->isTruthy($row['ignored']);
            $groups[$key]['dateCreated'] = $this->earlierDate($groups[$key]['dateCreated'], $row['dateCreated']);
            $groups[$key]['dateUpdated'] = $this->laterDate($groups[$key]['dateUpdated'], $row['dateUpdated']);

            if ($row['referrer'] && $this->compareDates($row['dateUpdated'], $groups[$key]['referrerDate']) >= 0) {
                $groups[$key]['referrer'] = $row['referrer'];
                $groups[$key]['referrerDate'] = $row['dateUpdated'];
            }
        }

        foreach ($groups as $group) {
            if (!$group['duplicateIds']) {
                continue;
            }

            $this->update(self::TABLE, [
                'hitCount' => $group['hitCount'],
                'ignored' => $group['ignored'],
                'referrer' => $group['referrer'],
                'dateCreated' => $group['dateCreated'],
                'dateUpdated' => $group['dateUpdated'],
            ], ['id' => $group['id']]);

            $this->delete(self::TABLE, ['id' => $group['duplicateIds']]);
        }
    }

    private function uniqueIndexExists(): bool
    {
        $schema = $this->db->getSchema();
        if (!method_exists($schema, 'findIndexes')) {
            return false;
        }

        foreach ($schema->findIndexes(self::TABLE) as $index) {
            if (
                ($index['unique'] ?? false) &&
                array_values($index['columns'] ?? []) === self::UNIQUE_COLUMNS
            ) {
                return true;
            }
        }

        return false;
    }

    private function earlierDate(mixed $a, mixed $b): mixed
    {
        return $this->compareDates($a, $b) <= 0 ? $a : $b;
    }

    private function laterDate(mixed $a, mixed $b): mixed
    {
        return $this->compareDates($a, $b) >= 0 ? $a : $b;
    }

    private function compareDates(mixed $a, mixed $b): int
    {
        if (!$a && !$b) {
            return 0;
        }
        if (!$a) {
            return -1;
        }
        if (!$b) {
            return 1;
        }

        return $this->dateTimestamp($a) <=> $this->dateTimestamp($b);
    }

    private function dateTimestamp(mixed $value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        return strtotime((string)$value) ?: 0;
    }

    private function isTruthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }
}
