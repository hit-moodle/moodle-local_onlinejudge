# Frankenstyle Naming Compliance Changes

This document summarizes the changes made to comply with Moodle's frankenstyle naming conventions.

## Changes Made

### 1. Exception Class
- **Old**: `onlinejudge_exception` (in `exceptions.php`)
- **New**: `\local_onlinejudge\exception` (in `classes/exception.php`)

### 2. Judge Base Class
- **Old**: `judge_base` (in `judgelib.php`)
- **New**: `\local_onlinejudge\judge\base` (in `classes/judge/base.php`)

### 3. Judge Sandbox Class
- **Old**: `judge_sandbox` (in `judge/sandbox/lib.php`)
- **New**: `\local_onlinejudge\judge\sandbox` (in `classes/judge/sandbox.php`)

### 4. Judge Sphere Engine Class
- **Old**: `judge_sphere_engine` (in `judge/sphere_engine/lib.php`)
- **New**: `\local_onlinejudge\judge\sphere_engine` (in `classes/judge/sphere_engine.php`)

### 5. Event Class
- **Old**: `onlinejudge_task_judged` (in `classes/event/onlinejudge_task_judged.php`)
- **New**: `task_judged` (in `classes/event/task_judged.php`)
- **Namespace**: Changed from `mod_onlinejudge\event` to `local_onlinejudge\event`

## Backward Compatibility

To ensure existing code continues to work, backward compatibility aliases have been added in `judgelib.php`:

```php
// Backward compatibility aliases
if (!class_exists('onlinejudge_exception')) {
    class_alias('\\local_onlinejudge\\exception', 'onlinejudge_exception');
}
if (!class_exists('judge_base')) {
    class_alias('\\local_onlinejudge\\judge\\base', 'judge_base');
}
if (!class_exists('judge_sandbox')) {
    class_alias('\\local_onlinejudge\\judge\\sandbox', 'judge_sandbox');
}
if (!class_exists('judge_sphere_engine')) {
    class_alias('\\local_onlinejudge\\judge\\sphere_engine', 'judge_sphere_engine');
}
```

## Files Structure

The new frankenstyle-compliant structure is:

```
classes/
├── exception.php                    # \local_onlinejudge\exception
├── judge/
│   ├── base.php                    # \local_onlinejudge\judge\base
│   ├── sandbox.php                 # \local_onlinejudge\judge\sandbox
│   └── sphere_engine.php           # \local_onlinejudge\judge\sphere_engine
└── event/
    └── task_judged.php             # \local_onlinejudge\event\task_judged
```

## Migration Guide

### For New Code
Use the new namespaced classes:
```php
// Use this
use local_onlinejudge\exception;
use local_onlinejudge\judge\base;
use local_onlinejudge\judge\sandbox;
use local_onlinejudge\judge\sphere_engine;
use local_onlinejudge\event\task_judged;
```

### For Existing Code
No changes required - old class names will continue to work due to backward compatibility aliases.

## Language Strings

Added new language strings for the renamed event:
- `event_task_judged` - "Online Judge Task Judged"
- `event_task_judged_description` - "The event is fired when an online judge task has been judged."

## Benefits

1. **Compliance**: Meets Moodle's frankenstyle naming requirements for plugin database approval
2. **Autoloading**: Classes can be autoloaded using Moodle's class loader
3. **Namespace**: Proper namespace organization prevents naming conflicts
4. **Maintainability**: Clear class organization in the `classes/` directory
5. **Compatibility**: Existing code continues to work without modification

## Notes

- The old files in `judge/` directory are still present for backward compatibility
- All functions in `judgelib.php` retain their original names (they follow correct naming)
- The plugin continues to work exactly as before with no functional changes