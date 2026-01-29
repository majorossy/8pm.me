<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

class HistoryDataProvider extends DataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();

        // Add custom processing if needed
        // For example, calculate duration from started_at and completed_at
        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['started_at']) && isset($item['completed_at'])) {
                    $start = strtotime($item['started_at']);
                    $end = strtotime($item['completed_at']);
                    if ($start && $end) {
                        $duration = $end - $start;
                        $item['duration'] = $this->formatDuration($duration);
                    }
                } elseif (isset($item['started_at']) && $item['status'] === 'running') {
                    $start = strtotime($item['started_at']);
                    $now = time();
                    $duration = $now - $start;
                    $item['duration'] = $this->formatDuration($duration) . ' (running)';
                }
            }
        }

        return $data;
    }

    /**
     * Format duration in human-readable format
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $minutes . 'm ' . $secs . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
}
