<?php

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

if (! function_exists('activity')) {
    /**
     * Record an activity log entry
     *
     * @param  string  $logType  Type of log (create, update, delete, etc.)
     * @param  string  $description  Human-readable description of the activity
     * @param  \Illuminate\Database\Eloquent\Model|null  $subject  Related model instance
     * @param  array  $properties  Additional custom properties to store
     * @return \App\Models\ActivityLog
     */
    function activity(string $logType, string $description, ?Model $subject = null, array $properties = [])
    {
        // Get authenticated user if available
        $userId = Auth::check() ? Auth::id() : null;

        // Get model information if subject is provided
        $modelType = null;
        $modelId = null;

        if ($subject) {
            // Extract the class name without namespace
            $className = class_basename($subject);
            $modelType = $className;
            $modelId = $subject->getKey();

            // Add subject attributes to properties if not sensitive/large
            if (! isset($properties['subject'])) {
                $attributes = $subject->getAttributes();

                // Filter out any potentially sensitive fields
                $sensitiveFields = ['password', 'remember_token', 'api_token'];
                foreach ($sensitiveFields as $field) {
                    if (isset($attributes[$field])) {
                        unset($attributes[$field]);
                    }
                }

                $properties['subject'] = $attributes;
            }
        }

        // Include request information
        $properties['request'] = [
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'route' => Request::route() ? Request::route()->getName() : null,
        ];

        return ActivityLog::create([
            'user_id' => $userId,
            'log_type' => $logType,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}

if (! function_exists('activityFor')) {
    /**
     * Create a log entry for a specific model action
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  The model being acted upon
     * @param  string  $action  Action performed (created, updated, deleted, etc.)
     * @param  string|null  $description  Optional custom description
     * @param  array  $properties  Additional properties to log
     * @return \App\Models\ActivityLog
     */
    function activityFor(Model $model, string $action, ?string $description = null, array $properties = [])
    {
        // Generate sensible description if not provided
        if ($description === null) {
            $modelName = class_basename($model);
            $identifier = $model->getKey();

            // Try to find a more human-readable identifier if available
            foreach (['name', 'title', 'email', 'username'] as $field) {
                if (isset($model->$field) && ! empty($model->$field)) {
                    $identifier = $model->$field;
                    break;
                }
            }

            $description = ucfirst($action)." $modelName #$identifier";
        }

        // Track what changed if this was an update
        if ($action === 'updated' && method_exists($model, 'getChanges')) {
            $properties['changes'] = [
                'old' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                'new' => $model->getChanges(),
            ];
        }

        return activity($action, $description, $model, $properties);
    }
}

if (! function_exists('getLogTypeColor')) {
    function getLogTypeColor($log)
    {
        switch (strtolower($log->log_type)) {
            case 'created':
                return 'text-success';
            case 'updated':
                return 'text-primary';
            case 'deleted':
                return 'text-danger';
            case 'accessed':
                return 'text-info';
            case 'login':
                return 'text-success';
            case 'logout':
                return 'text-warning';
            default:
                return 'text-secondary';
        }
    }
}

if (! function_exists('getBorderStyle')) {
    function getBorderStyle($log)
    {
        switch (strtolower($log->log_type)) {
            case 'created':
                return 'brd-l-success';
            case 'updated':
                return 'brd-l-primary';
            case 'deleted':
                return 'brd-l-danger';
            case 'accessed':
                return 'brd-l-info';
            case 'login':
                return 'brd-l-success';
            case 'logout':
                return 'brd-l-warning';
            default:
                return 'brd-l-default';
        }
    }
}
