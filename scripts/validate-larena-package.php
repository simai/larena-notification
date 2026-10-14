<?php

declare(strict_types=1);

$requiredFiles = [
    '.gitignore',
    '.env.example',
    '.github/workflows/larena-package-ci.yml',
    '.githooks/pre-commit',
    '.githooks/pre-push',
    'composer.json',
    'module.yaml',
    'phpstan.neon.dist',
    '.larena/spec-ref.json',
    '.larena/launch-context.json',
    'tools/larena-scope-check.php',
];
$errors = [];
foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        $errors[] = "Missing required enforcement file: {$file}";
    }
}
$specRef = is_file('.larena/spec-ref.json')
    ? json_decode((string) file_get_contents('.larena/spec-ref.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
$launchContext = is_file('.larena/launch-context.json')
    ? json_decode((string) file_get_contents('.larena/launch-context.json'), true, 512, JSON_THROW_ON_ERROR)
    : [];
if (($specRef['canonical_update_allowed'] ?? null) !== false) {
    $errors[] = '.larena/spec-ref.json must keep canonical_update_allowed=false';
}
if (($launchContext['package'] ?? null) !== 'larena/notification') {
    $errors[] = '.larena/launch-context.json package must be larena/notification';
}
if (!str_starts_with((string) ($launchContext['evidence_path'] ?? ''), 'docs/project-management/evidence/')) {
    $errors[] = 'launch-context evidence_path must start with docs/project-management/evidence/';
}
if (!str_starts_with((string) ($launchContext['graph_sync_proposal_path'] ?? ''), (string) ($launchContext['evidence_path'] ?? '__missing__'))) {
    $errors[] = 'graph_sync_proposal_path must be inside evidence_path';
}
$allowedStatuses = [
    'repository_prepared_pending_review',
    'coding_started',
    'contract_skeleton_review_passed',
];
if (!in_array((string) ($launchContext['status'] ?? ''), $allowedStatuses, true)) {
    $errors[] = 'launch-context status is not allowed for this package stage.';
}

$codingStarted = ($launchContext['coding_started'] ?? null) === true;
if (!$codingStarted) {
    foreach (['src', 'config', 'database', 'routes', 'resources', 'tests', 'lang'] as $runtimePath) {
        if (is_dir($runtimePath)) {
            $errors[] = "{$runtimePath}/ is not allowed before a coding launch record.";
        }
    }
}

if ($codingStarted) {
    $launchRecord = (string) ($launchContext['launch_record_ref'] ?? '');
    if ($launchRecord !== 'docs/project-management/launch-records/notification-inbox-v1.json' || !is_file($launchRecord)) {
        $errors[] = 'coding_started requires the Notification inbox v1 launch record.';
    }

    $requiredContractFiles = [
        'src/Contracts/DeliveryAttempt.php',
        'src/Contracts/DeliveryChannel.php',
        'src/Contracts/NotificationAdminItem.php',
        'src/Contracts/NotificationEventDescriptor.php',
        'src/Contracts/NotificationGateway.php',
        'src/Contracts/NotificationMessage.php',
        'src/Contracts/RecipientPreference.php',
        'src/Contracts/RecipientRef.php',
        'src/Contracts/TemplateDescriptor.php',
        'src/Enums/ChannelKind.php',
        'src/Enums/DeliveryAttemptState.php',
        'src/Enums/NotificationSeverity.php',
        'src/Enums/PreferenceDecision.php',
        'tests/Unit/NotificationContractTest.php',
        'tests/Unit/NotificationFailsClosedTest.php',
        'database/migrations/2026_10_14_000001_create_larena_notifications_table.php',
        'src/Runtime/NotificationInbox.php',
        'src/Providers/NotificationServiceProvider.php',
    ];
    foreach ($requiredContractFiles as $file) {
        if (!is_file($file)) {
            $errors[] = "Missing required Notification contract skeleton file: {$file}";
        }
    }
}
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}
echo $codingStarted
    ? "Larena Notification inbox launch context is valid.\n"
    : "Larena Notification clean pre-codegen baseline is valid.\n";
