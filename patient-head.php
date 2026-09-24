<?php
/**
 * Shared <head> for the redesigned patient (user) pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? esc($pageTitle) . ' — Bed Track' : 'Bed Track'; ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #f4fbf4; font-family: 'Inter', sans-serif; }
  .material-symbols-outlined { 
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    font-size: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .material-symbols-outlined.filled {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  }
  .booking-table th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #3c4a42;
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #bbcabf;
  }
  .booking-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #dde4dd;
    font-size: 14px;
  }
  .booking-table tr:hover {
    background: #f4fbf4;
  }
  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid transparent;
  }
  .status-pill-pending { background: #fbf0dc; color: #b4780f; border-color: #f5d99b; }
  .status-pill-accepted { background: #e3f2ed; color: #0a4e3e; border-color: #a8d5c4; }
  .status-pill-discharged { background: #e8f0f8; color: #2f5e8c; border-color: #b8cfe5; }
  .status-pill-rejected { background: #fbe9e4; color: #b8492b; border-color: #ecc4b8; }
  .fade-out {
    opacity: 0 !important;
    transform: translateY(-8px);
    transition: all 0.3s ease;
  }
  .toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }
  .toast {
    pointer-events: auto;
    min-width: 280px;
    max-width: 420px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12), 0 8px 10px -6px rgba(0,0,0,0.06);
    animation: toastSlideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    transition: opacity 0.25s ease, transform 0.25s ease;
  }
  @keyframes toastSlideIn {
    from { opacity: 0; transform: translateY(-12px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  .toast.toast-hide { opacity: 0; transform: translateY(-8px) scale(0.96); }
  .toast-success { background: #ffffff; color: #0891b2; border: 1px solid #cff4fc; border-left: 4px solid #06b6d4; }
  .toast-error { background: #ffffff; color: #991b1b; border: 1px solid #fee2e2; border-left: 4px solid #ef4444; }
  .toast-info { background: #ffffff; color: #1e40af; border: 1px solid #dbeafe; border-left: 4px solid #3b82f6; }
  .toast-close { background: none; border: none; color: inherit; opacity: 0.5; cursor: pointer; padding: 2px 4px; font-size: 18px; line-height: 1; }
  .toast-close:hover { opacity: 1; }
  .password-field-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
  }
  .password-field-wrapper input {
    padding-right: 42px !important;
  }
  .password-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #55635b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    line-height: 1;
    z-index: 5;
    transition: color 0.15s ease;
  }
  .password-toggle-btn:hover {
    color: #161d19;
  }
</style>
</head>
<body class="bg-[#f4fbf4] text-[#161d19] text-[14px] leading-5 antialiased min-h-screen">