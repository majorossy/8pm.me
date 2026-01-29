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

        // Format duration_seconds for display
        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                // Use duration_seconds if available (from database)
                if (isset($item['duration_seconds']) && $item['duration_seconds'] > 0) {
                    $item['duration_display'] = $this->formatDuration((int)$item['duration_seconds']);
                } elseif (isset($item['started_at']) && isset($item['completed_at'])) {
                    // Fallback: calculate from timestamps
                    $start = strtotime($item['started_at']);
                    $end = strtotime($item['completed_at']);
                    if ($start && $end) {
                        $duration = $end - $start;
                        $item['duration_display'] = $this->formatDuration($duration);
                    }
                } elseif (isset($item['started_at']) && $item['status'] === 'running') {
                    // Running: calculate elapsed time
                    $start = strtotime($item['started_at']);
                    $now = time();
                    $duration = $now - $start;
                    $item['duration_display'] = $this->formatDuration($duration) . ' (running)';
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
