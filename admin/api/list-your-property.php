<?php
/**
 * Admin API: review public property submissions (approve / reject / update).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/PropertyRepository.php';
require_once dirname(__DIR__, 2) . '/includes/PropertyUploadService.php';
require_once dirname(__DIR__, 2) . '/includes/AutomatedEmailService.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $property = PropertyRepository::getById((int) $_GET['id']);
            if ($property === null || ($property['source'] ?? '') !== 'public') {
                jsonError('Submission not found.', 404);
            }
            jsonOk(['submission' => $property]);
        }

        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $submissions = PropertyRepository::getPublicSubmissions($status, $search);

        jsonOk([
            'submissions' => $submissions,
            'stats'       => PropertyRepository::getPublicSubmissionStats(),
        ]);
    }

    if ($method === 'POST') {
        if (($_POST['action'] ?? '') === 'update') {
            handleUpdateSubmission();
        }

        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
        $action = (string) ($body['action'] ?? '');
        $id = (int) ($body['id'] ?? 0);

        if ($id <= 0) {
            jsonError('Invalid submission ID.');
        }

        $property = PropertyRepository::getById($id);
        if ($property === null || ($property['source'] ?? '') !== 'public') {
            jsonError('Submission not found.', 404);
        }

        if ($action === 'approve') {
            $oldStatus = (string) ($property['approvalStatus'] ?? 'pending');
            $updated = PropertyRepository::setApprovalStatus($id, 'approved', trim((string) ($body['notes'] ?? '')) ?: null);
            AutomatedEmailService::onPropertyStatusChange(
                $updated,
                $oldStatus,
                'approved',
                (int) ($_SESSION['admin_id'] ?? 0)
            );
            jsonOk(['submission' => $updated, 'message' => 'Property approved and published to the website.']);
        }

        if ($action === 'reject') {
            $notes = trim((string) ($body['notes'] ?? ''));
            if ($notes === '') {
                jsonError('Please provide a reason for rejection.');
            }
            $oldStatus = (string) ($property['approvalStatus'] ?? 'pending');
            $updated = PropertyRepository::setApprovalStatus($id, 'rejected', $notes);
            AutomatedEmailService::onPropertyStatusChange(
                $updated,
                $oldStatus,
                'rejected',
                (int) ($_SESSION['admin_id'] ?? 0),
                $notes
            );
            jsonOk(['submission' => $updated, 'message' => 'Property submission rejected.']);
        }

        if ($action === 'mark_pending') {
            $oldStatus = (string) ($property['approvalStatus'] ?? 'pending');
            $updated = PropertyRepository::setApprovalStatus($id, 'pending', trim((string) ($body['notes'] ?? '')) ?: null);
            AutomatedEmailService::onPropertyStatusChange(
                $updated,
                $oldStatus,
                'pending',
                (int) ($_SESSION['admin_id'] ?? 0),
                trim((string) ($body['notes'] ?? '')) ?: null
            );
            jsonOk(['submission' => $updated, 'message' => 'Submission moved back to pending review.']);
        }

        jsonError('Unknown action.');
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid submission ID.');
        }

        $property = PropertyRepository::getById($id);
        if ($property === null || ($property['source'] ?? '') !== 'public') {
            jsonError('Submission not found.', 404);
        }

        PropertyRepository::delete($id);
        jsonOk(['message' => 'Submission deleted.']);
    }

    jsonError('Method not allowed.', 405);
} catch (PDOException $e) {
    jsonError('Database error. Run sql/migrate_properties_v3.php if this is a new install.', 503);
} catch (Throwable $e) {
    jsonError($e->getMessage() ?: 'Request failed.', 400);
}

function handleUpdateSubmission(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonError('Invalid submission ID.');
    }

    $property = PropertyRepository::getById($id);
    if ($property === null || ($property['source'] ?? '') !== 'public') {
        jsonError('Submission not found.', 404);
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $address = trim((string) ($_POST['propertyAddress'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $ownerName = trim((string) ($_POST['ownerName'] ?? ''));
    $ownerPhone = trim((string) ($_POST['ownerPhone'] ?? ''));
    $listingPurpose = trim((string) ($_POST['listingPurpose'] ?? ''));
    $price = PropertyUploadService::parsePrice((string) ($_POST['price'] ?? '0'));

    if ($title === '' || $location === '' || $address === '' || $description === '' || $ownerName === '' || $ownerPhone === '') {
        jsonError('Please complete all required fields.');
    }

    if ($price <= 0) {
        jsonError('Please enter a valid price.');
    }

    $keepImagesRaw = $_POST['keepImages'] ?? '[]';
    if (is_array($keepImagesRaw)) {
        $keepImages = $keepImagesRaw;
    } else {
        $keepImages = json_decode((string) $keepImagesRaw, true);
        $keepImages = is_array($keepImages) ? $keepImages : [];
    }

    $removeVideo = (string) ($_POST['removeVideo'] ?? '0') === '1';
    $listingType = PropertyUploadService::mapListingType(
        $listingPurpose !== '' ? $listingPurpose : (string) ($property['listingPurpose'] ?? 'sale')
    );

    $media = PropertyUploadService::updateSubmissionMedia(
        $id,
        $keepImages,
        $_FILES,
        $removeVideo,
        $property['storedVideo'] ?? null
    );

    $updated = PropertyRepository::update($id, [
        'title'            => $title,
        'price'            => $price,
        'type'             => $listingType,
        'location'         => $location,
        'bedrooms'         => max(0, (int) ($_POST['bedrooms'] ?? $property['bedrooms'] ?? 0)),
        'bathrooms'        => max(0, (int) ($_POST['bathrooms'] ?? $property['bathrooms'] ?? 0)),
        'area'             => max(0, (int) preg_replace('/[^\d]/', '', (string) ($_POST['area'] ?? $property['area'] ?? '0'))),
        'description'      => $description,
        'ownerName'        => $ownerName,
        'ownerEmail'       => trim((string) ($_POST['ownerEmail'] ?? '')) ?: null,
        'ownerPhone'       => $ownerPhone,
        'contactMethod'    => trim((string) ($_POST['contactMethod'] ?? '')) ?: null,
        'listingPurpose'   => $listingPurpose !== '' ? $listingPurpose : ($property['listingPurpose'] ?? null),
        'propertyCategory' => trim((string) ($_POST['propertyCategory'] ?? '')) ?: null,
        'propertyAddress'  => $address,
        'propertyFeatures' => trim((string) ($_POST['propertyFeatures'] ?? '')) ?: null,
        'ownershipStatus'  => trim((string) ($_POST['ownershipStatus'] ?? '')) ?: null,
        'propertySize'     => trim((string) ($_POST['propertySize'] ?? '')) ?: null,
        'adminNotes'       => trim((string) ($_POST['adminNotes'] ?? '')) ?: null,
        'imageUrl'         => $media['imageUrl'],
        'videoUrl'         => $media['videoUrl'],
        'galleryUrls'      => $media['galleryUrls'],
    ]);

    jsonOk(['submission' => $updated, 'message' => 'Submission updated successfully.']);
}

function jsonOk(array $data): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
