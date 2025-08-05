<?php
// app/Traits/LogsActivity.php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Log aktivitas create
     */
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    /**
     * Log aktivitas
     */
    public function logActivity($action, $changes = null)
    {
        $logData = [
            'model' => get_class($this),
            'model_id' => $this->getKey(),
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ];

        if ($changes) {
            $logData['changes'] = $changes;
        }

        Log::info("Model {$action}", $logData);
    }

    /**
     * Log custom activity
     */
    public function logCustomActivity($action, $description = null, $data = null)
    {
        Log::info("Custom activity: {$action}", [
            'model' => get_class($this),
            'model_id' => $this->getKey(),
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
