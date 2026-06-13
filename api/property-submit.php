<?php
/**
 * Public API: submit a property listing for admin approval.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/includes/PropertyRepository.php';
require_once dirname(__DIR__) . '/includes/PropertyUploadService.php';
require_once dirname(__DIR__) . '/includes/AutomatedEmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $ownerName = trim((string) ($_POST['ownerName'] ?? ''));
    $ownerEmail = strtolower(trim((string) ($_POST['ownerEmail'] ?? '')));
    $ownerPhone = trim((string) ($_POST['ownerPhone'] ?? ''));
    $title = trim((string) ($_POST['propertyTitle'] ?? ''));
    $location = trim((string) ($_POST['propertyLocation'] ?? ''));
    $address = trim((string) ($_POST['propertyAddress'] ?? ''));
    $description = trim((string) ($_POST['propertyDescription'] ?? ''));
    $listingPurpose = trim((string) ($_POST['listingType'] ?? ''));
    $price = PropertyUploadService::parsePrice((string) ($_POST['propertyPrice'] ?? '0'));

    if ($ownerName === '' || $ownerEmail === '' || $ownerPhone === '' || $title === '' || $location === '' || $address === '' || $description === '') {
        throw new InvalidArgumentException('Please complete all required fields.');
    }

    if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid owner email address.');
    }

    if ($listingPurpose === '') {
        throw new InvalidArgumentException('Please select a listing purpose.');
    }

    if ($price <= 0) {
        throw new InvalidArgumentException('Please enter a valid asking price.');
    }

    if (empty($_FILES['propertyImages'])) {
        throw new InvalidArgumentException('Please upload at least one property image.');
    }

    $property = PropertyRepository::createPublicSubmission([
        'title'            => $title,
        'price'            => $price,
        'type'             => PropertyUploadService::mapListingType($listingPurpose),
        'location'         => $location,
        'bedrooms'         => (int) ($_POST['bedrooms'] ?? 0),
        'bathrooms'        => (int) ($_POST['bathrooms'] ?? 0),
        'area'             => (int) preg_replace('/[^\d]/', '', (string) ($_POST['propertySize'] ?? '0')),
        'description'      => buildDescription($description, $_POST),
        'ownerName'        => $ownerName,
        'ownerEmail'       => $ownerEmail,
        'ownerPhone'       => $ownerPhone,
        'contactMethod'    => trim((string) ($_POST['contactMethod'] ?? '')) ?: null,
        'listingPurpose'   => $listingPurpose,
        'propertyCategory' => trim((string) ($_POST['propertyType'] ?? '')) ?: null,
        'propertyAddress'  => $address,
        'propertyFeatures' => trim((string) ($_POST['propertyFeatures'] ?? '')) ?: null,
        'ownershipStatus'  => trim((string) ($_POST['ownershipStatus'] ?? '')) ?: null,
        'propertySize'     => trim((string) ($_POST['propertySize'] ?? '')) ?: null,
    ]);

    $media = PropertyUploadService::storeSubmissionMedia((int) $property['id'], $_FILES);
    if ($media['imageUrl'] === null) {
        PropertyRepository::delete((int) $property['id']);
        throw new RuntimeException('Image upload failed.');
    }

    $property = PropertyRepository::update((int) $property['id'], [
        'imageUrl'    => $media['imageUrl'],
        'videoUrl'    => $media['videoUrl'],
        'galleryUrls' => $media['extraImages'],
    ]);

    try {
        AutomatedEmailService::onPropertySubmitted($property);
    } catch (Throwable $mailEx) {
        error_log('Property submission auto-email failed: ' . $mailEx->getMessage());
    }

    echo json_encode([
        'success'  => true,
        'message'  => 'Listing sent successfully. Waiting for admin approval.',
        'property' => $property,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Submission failed. Please try again.']);
}

/**
 * @param array<string, mixed> $post
 */
function buildDescription(string $description, array $post): string
{
    $notes = trim((string) ($post['sellerNotes'] ?? $post['additionalNotes'] ?? ''));
    if ($notes === '') {
        return $description;
    }

    return $description . "\n\nAdditional notes:\n" . $notes;
}
