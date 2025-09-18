<?php


// Handle export requests FIRST - before any HTML output
if (isset($_GET['export'])) {
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    handleExport();
}

$pageTitle = 'Attendance Reports';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Attendance Reports']
];

require_once '../includes/header.php';

function handleExport() {
    try {
        // Clean any output that might have been started
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $reportType = $_GET['export'];
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $constituency = $_GET['constituency'] ?? '';
        $mandal = $_GET['mandal'] ?? '';
        $batch = $_GET['batch'] ?? '';
        
        switch ($reportType) {
            case 'attendance':
                exportAttendanceReport($startDate, $endDate, $constituency, $mandal, $batch);
                break;
            case 'beneficiaries':
                exportBeneficiariesReport($constituency, $mandal, $batch);
                break;
            case 'summary':
                exportSummaryReport($startDate, $endDate, $constituency, $mandal, $batch);
                break;
            default:
                error_log("Unknown export type: " . $reportType);
                header('Content-Type: text/plain');
                die("Invalid export type: " . htmlspecialchars($reportType));
        }
    } catch (Exception $e) {
        error_log("Export error: " . $e->getMessage());
        header('Content-Type: text/plain');
        die("Export failed: " . $e->getMessage());
    }
}

function exportAttendanceReport($startDate, $endDate, $constituency, $mandal, $batch) {
    // Get all beneficiaries with their details
    $beneficiariesQuery = "SELECT 
        b.id as beneficiary_id,
        b.beneficiary_id as beneficiary_code,
        b.full_name,
        b.mobile_number,
        b.aadhar_number,
        COALESCE(NULLIF(b.batch_start_date, '1970-01-01'), bt.start_date) as batch_start_date,
        COALESCE(NULLIF(b.batch_end_date, '1970-01-01'), bt.end_date) as batch_end_date,
        c.name as constituency_name,
        m.name as mandal_name,
        bt.name as batch_name,
        bt.code as batch_code,
        tc.tc_id as training_center_id
    FROM beneficiaries b
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    LEFT JOIN training_centers tc ON bt.tc_id = tc.id
    WHERE b.status IN ('active', 'completed')"; // Include both active and completed beneficiaries
    
    $beneficiaryParams = [];
    $beneficiaryTypes = '';
    
    if (!empty($constituency)) {
        $beneficiariesQuery .= " AND b.constituency_id = ?";
        $beneficiaryParams[] = $constituency;
        $beneficiaryTypes .= 'i';
    }
    
    if (!empty($mandal)) {
        $beneficiariesQuery .= " AND b.mandal_id = ?";
        $beneficiaryParams[] = $mandal;
        $beneficiaryTypes .= 'i';
    }
    
    if (!empty($batch)) {
        $beneficiariesQuery .= " AND b.batch_id = ?";
        $beneficiaryParams[] = $batch;
        $beneficiaryTypes .= 'i';
    }
    
    $beneficiariesQuery .= " ORDER BY m.name, bt.name, b.full_name";
    $beneficiaries = fetchAll($beneficiariesQuery, $beneficiaryParams, $beneficiaryTypes);
    
    // Debug logging
    error_log("Export Attendance Report - Beneficiaries found: " . count($beneficiaries));
    error_log("Export Attendance Report - Date range: $startDate to $endDate");
    error_log("Export Attendance Report - Filters: constituency=$constituency, mandal=$mandal, batch=$batch");
    
    // Get date range
    $startDateTime = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $dateRange = [];
    $currentDate = clone $startDateTime;
    
    while ($currentDate <= $endDateTime) {
        $dateRange[] = $currentDate->format('Y-m-d');
        $currentDate->add(new DateInterval('P1D'));
    }
    

    
    // Get attendance data for the date range - get ALL attendance records first
    $attendanceQuery = "SELECT 
        a.beneficiary_id,
        a.attendance_date,
        a.status
    FROM attendance a
    WHERE a.attendance_date BETWEEN ? AND ?";
    
    $attendanceParams = [$startDate, $endDate];
    $attendanceTypes = 'ss';
    
    $attendanceData = fetchAll($attendanceQuery, $attendanceParams, $attendanceTypes);
    
    // Debug logging
    error_log("Export Attendance Report - Attendance records found: " . count($attendanceData));
    
    // Organize attendance data by beneficiary and date
    $attendanceByBeneficiary = [];
    foreach ($attendanceData as $attendance) {
        $beneficiaryId = $attendance['beneficiary_id'];
        $date = $attendance['attendance_date'];
        $status = $attendance['status'];
        
        if (!isset($attendanceByBeneficiary[$beneficiaryId])) {
            $attendanceByBeneficiary[$beneficiaryId] = [];
        }
        $attendanceByBeneficiary[$beneficiaryId][$date] = $status;
    }
    

    
    // Create CSV file (Excel compatible)
    exportAttendanceToCSV($beneficiaries, $dateRange, $attendanceByBeneficiary, $startDate, $endDate);
}

function exportBeneficiariesReport($constituency, $mandal, $batch) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($constituency)) {
        $whereConditions[] = "b.constituency_id = ?";
        $params[] = $constituency;
        $types .= 'i';
    }
    
    if (!empty($mandal)) {
        $whereConditions[] = "b.mandal_id = ?";
        $params[] = $mandal;
        $types .= 'i';
    }
    
    if (!empty($batch)) {
        $whereConditions[] = "b.batch_id = ?";
        $params[] = $batch;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT 
        b.beneficiary_id,
        b.full_name,
        b.mobile_number,
        c.name as constituency_name,
        m.name as mandal_name,
        bt.name as batch_name,
        b.aadhar_number,
        b.batch_start_date,
        b.batch_end_date,
        b.status,
        b.created_at
    FROM beneficiaries b
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    $whereClause
    ORDER BY b.beneficiary_id";
    
    $data = fetchAll($query, $params, $types);
    
    $headers = ['Beneficiary ID', 'Name', 'Mobile', 'Constituency', 'Mandal', 'Batch', 'Aadhar Number', 'Batch Start Date', 'Batch End Date', 'Status', 'Registration Date'];
    
    $csvData = [];
    foreach ($data as $row) {
        $csvData[] = [
            $row['beneficiary_id'],
            $row['full_name'],
            $row['mobile_number'] ?? '',
            $row['constituency_name'] ?? 'N/A',
            $row['mandal_name'] ?? 'N/A',
            $row['batch_name'] ?? 'N/A',
            $row['aadhar_number'] ?? '',
            formatDate($row['batch_start_date'], 'd/m/Y'),
            formatDate($row['batch_end_date'], 'd/m/Y'),
            ucfirst($row['status'] ?? 'N/A'),
            formatDate($row['created_at'], 'd/m/Y')
        ];
    }
    
    exportToCSV('beneficiaries_report_' . date('Y-m-d_H-i-s') . '.csv', $csvData, $headers);
}

function exportSummaryReport($startDate, $endDate, $constituency, $mandal, $batch) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($startDate)) {
        $whereConditions[] = "a.attendance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    
    if (!empty($endDate)) {
        $whereConditions[] = "a.attendance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    
    if (!empty($constituency)) {
        $whereConditions[] = "c.id = ?";
        $params[] = $constituency;
        $types .= 'i';
    }
    
    if (!empty($mandal)) {
        $whereConditions[] = "m.id = ?";
        $params[] = $mandal;
        $types .= 'i';
    }
    
    if (!empty($batch)) {
        $whereConditions[] = "bt.id = ?";
        $params[] = $batch;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get constituency-wise summary
    $constituencyQuery = "SELECT 
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'H' THEN 1 ELSE 0 END) as holiday_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    $whereClause
    GROUP BY c.id, c.name
    ORDER BY c.name";
    
    $constituencyData = fetchAll($constituencyQuery, $params, $types);
    
    // Get mandal-wise summary
    $mandalQuery = "SELECT 
        m.name as mandal_name,
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'H' THEN 1 ELSE 0 END) as holiday_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    $whereClause
    GROUP BY b.mandal_id, m.name, c.name
    ORDER BY m.name";
    
    $mandalData = fetchAll($mandalQuery, $params, $types);
    
    // Get batch-wise summary
    $batchQuery = "SELECT 
        bt.name as batch_name,
        m.name as mandal_name,
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'H' THEN 1 ELSE 0 END) as holiday_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    $whereClause
    GROUP BY b.batch_id, bt.name, m.name, c.name
    ORDER BY bt.name";
    
    $batchData = fetchAll($batchQuery, $params, $types);
    
    // Combine all data for CSV export
    $csvData = [];
    
    // Add constituency data
    $csvData[] = ['CONSTITUENCY SUMMARY'];
    $csvData[] = ['Constituency', 'Total Records', 'Present', 'Absent', 'Holiday', 'Attendance %'];
    foreach ($constituencyData as $row) {
        $attendanceRate = $row['total_records'] > 0 
            ? round(($row['present_count'] / $row['total_records']) * 100, 1) 
            : 0;
            
        $csvData[] = [
            $row['constituency_name'] ?? 'N/A',
            $row['total_records'],
            $row['present_count'],
            $row['absent_count'],
            $row['holiday_count'],
            $attendanceRate . '%'
        ];
    }
    
    // Add separator
    $csvData[] = [];
    $csvData[] = ['MANDAL SUMMARY'];
    $csvData[] = ['Mandal', 'Constituency', 'Total Records', 'Present', 'Absent', 'Holiday', 'Attendance %'];
    foreach ($mandalData as $row) {
        $attendanceRate = $row['total_records'] > 0 
            ? round(($row['present_count'] / $row['total_records']) * 100, 1) 
            : 0;
            
        $csvData[] = [
            $row['mandal_name'] ?? 'N/A',
            $row['constituency_name'] ?? 'N/A',
            $row['total_records'],
            $row['present_count'],
            $row['absent_count'],
            $row['holiday_count'],
            $attendanceRate . '%'
        ];
    }
    
    // Add separator
    $csvData[] = [];
    $csvData[] = ['BATCH SUMMARY'];
    $csvData[] = ['Batch', 'Mandal', 'Constituency', 'Total Records', 'Present', 'Absent', 'Holiday', 'Attendance %'];
    foreach ($batchData as $row) {
        $attendanceRate = $row['total_records'] > 0 
            ? round(($row['present_count'] / $row['total_records']) * 100, 1) 
            : 0;
            
        $csvData[] = [
            $row['batch_name'] ?? 'N/A',
            $row['mandal_name'] ?? 'N/A',
            $row['constituency_name'] ?? 'N/A',
            $row['total_records'],
            $row['present_count'],
            $row['absent_count'],
            $row['holiday_count'],
            $attendanceRate . '%'
        ];
    }
    
    exportToCSV('comprehensive_summary_report_' . date('Y-m-d_H-i-s') . '.csv', $csvData, []);
}

/*
function exportAttendanceToExcel($beneficiaries, $dateRange, $attendanceByBeneficiary, $startDate, $endDate) {
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Fallback to CSV if PhpSpreadsheet is not available
        exportAttendanceToCSV($beneficiaries, $dateRange, $attendanceByBeneficiary, $startDate, $endDate);
        return;
    }
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $title = "Attendance Report (" . date('d-m-Y', strtotime($startDate)) . " to " . date('d-m-Y', strtotime($endDate)) . ")";
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $sheet->getHighestColumn() . '1');
        
        // Style the title
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ];
        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        
        // Set headers
        $headers = [
            'S. NO',
            'Batch Start Date',
            'End Date', 
            'Mandal',
            'TC ID',
            'Phone No.',
            'Aadhar No.',
            'Name'
        ];
        
        // Add date columns
        foreach ($dateRange as $date) {
            $dayName = date('D', strtotime($date));
            $monthDay = date('j', strtotime($date));
            $year = date('Y', strtotime($date));
            $headers[] = $dayName . ', ' . $monthDay . '/' . $year;
        }
        
        // Write headers
        $col = 'A';
        $row = 3;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A3:' . $sheet->getHighestColumn() . '3')->applyFromArray($headerStyle);
        
        // Write data rows
        $row = 4;
        $serialNumber = 1;
        
        foreach ($beneficiaries as $beneficiary) {
            $col = 'A';
            
            // S. NO
            $sheet->setCellValue($col++, $serialNumber++);
            
            // Batch Start Date
            $sheet->setCellValue($col++, $beneficiary['batch_start_date'] ? date('j/n/Y', strtotime($beneficiary['batch_start_date'])) : '');
            
            // End Date
            $sheet->setCellValue($col++, $beneficiary['batch_end_date'] ? date('j/n/Y', strtotime($beneficiary['batch_end_date'])) : '');
            
            // Mandal
            $sheet->setCellValue($col++, $beneficiary['mandal_name'] ?? '');
            
            // TC ID (Batch Code)
            $sheet->setCellValue($col++, $beneficiary['batch_code'] ?? '');
            
            // Phone No.
            $sheet->setCellValue($col++, $beneficiary['mobile_number'] ?? '');
            
            // Aadhar No.
            $sheet->setCellValue($col++, $beneficiary['aadhar_number'] ?? '');
            
            // Name
            $sheet->setCellValue($col++, $beneficiary['full_name'] ?? '');
            
            // Attendance columns with enhanced holiday detection
            foreach ($dateRange as $date) {
                $status = $attendanceByBeneficiary[$beneficiary['beneficiary_id']][$date] ?? '';
                
                // Enhanced holiday detection logic
                $displayStatus = '';
                
                // First check if it's already marked as holiday
                if (strtoupper($status) === 'H' || strtoupper($status) === 'HOLIDAY') {
                    $displayStatus = 'H';
                }
                // Check if it's Sunday (should always be holiday)
                elseif (date('N', strtotime($date)) == 7) {
                    $displayStatus = 'H';
                }
                // Check if it's a custom holiday
                else {
                    // Check holidays table
                    $holidayCheck = fetchRow("SELECT id FROM holidays WHERE date = ?", [$date]);
                    if ($holidayCheck) {
                        $displayStatus = 'H';
                    }
                    // Check batch-specific holidays
                    else {
                        $batchHolidayCheck = fetchRow("SELECT id FROM batch_holidays WHERE holiday_date = ? AND batch_id = ?", 
                                                     [$date, $beneficiary['batch_id']]);
                        if ($batchHolidayCheck) {
                            $displayStatus = 'H';
                        }
                        // If not a holiday, use the original status
                        else {
                            switch (strtoupper($status)) {
                                case 'P':
                                case 'PRESENT':
                                    $displayStatus = 'P';
                                    break;
                                case 'A':
                                case 'ABSENT':
                                    $displayStatus = 'A';
                                    break;
                                default:
                                    $displayStatus = '';
                            }
                        }
                    }
                }
                
                $sheet->setCellValue($col++, $displayStatus);
            }
            
            $row++;
        }
        
        // Style data rows
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A4:' . $sheet->getHighestColumn() . ($row - 1))->applyFromArray($dataStyle);
        
        // Set filename
        $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        error_log("Excel export error: " . $e->getMessage());
        // Fallback to CSV
        exportAttendanceToCSV($beneficiaries, $dateRange, $attendanceByBeneficiary, $startDate, $endDate);
    }
}
*/

function exportAttendanceToCSV($beneficiaries, $dateRange, $attendanceByBeneficiary, $startDate, $endDate) {
    // Create output file
    $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.csv';
    $output = fopen('php://output', 'w');
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Write CSV headers
    $headers = [
        'S.No',
        'Batch Start Date',
        'Batch End Date', 
        'Constituency',
        'Mandal',
        'Training Center ID',
        'Batch Name',
        'Mobile Number',
        'Aadhar Number',
        'Full Name'
    ];
    
    // Add date columns
    foreach ($dateRange as $date) {
        $headers[] = date('j/n/Y', strtotime($date));
    }
    
    fputcsv($output, $headers);
    
    // Write data rows
    $serialNumber = 1;
    foreach ($beneficiaries as $beneficiary) {
        $row = [
            $serialNumber++,
            $beneficiary['batch_start_date'] ? date('j/n/Y', strtotime($beneficiary['batch_start_date'])) : '',
            $beneficiary['batch_end_date'] ? date('j/n/Y', strtotime($beneficiary['batch_end_date'])) : '',
            $beneficiary['constituency_name'] ?? '',
            $beneficiary['mandal_name'] ?? '',
            $beneficiary['training_center_id'] ?? '', // Actual TC ID like TTC7430653
            $beneficiary['batch_name'] ?? '',
            $beneficiary['mobile_number'] ?? '',
            $beneficiary['aadhar_number'] ?? '',
            $beneficiary['full_name'] ?? ''
        ];
        
        // Add attendance columns with enhanced holiday detection
        foreach ($dateRange as $date) {
            $status = $attendanceByBeneficiary[$beneficiary['beneficiary_id']][$date] ?? '';
            
            // Enhanced holiday detection logic with improved batch-specific checking
            $displayStatus = '';
            
            // First check if it's already marked as holiday
            if (strtoupper($status) === 'H' || strtoupper($status) === 'HOLIDAY') {
                $displayStatus = 'H';
            }
            // Check if it's Sunday (should always be holiday)
            elseif (date('N', strtotime($date)) == 7) {
                $displayStatus = 'H';
            }
            // Check if it's a custom holiday
            else {
                // Check if it's a batch-specific holiday first (IMPROVED LOGIC)
                $batchHolidayCheck = fetchRow("SELECT bh.id FROM batch_holidays bh 
                                             JOIN batches b ON bh.batch_id = b.id 
                                             WHERE bh.holiday_date = ? AND b.id = ?", 
                                             [$date, $beneficiary['batch_id']]);
                if ($batchHolidayCheck) {
                    $displayStatus = 'H';
                }
                // Check if it's an all-mandals holiday (no batch-specific entries)
                else {
                    $holidayCheck = fetchRow("SELECT h.id FROM holidays h 
                                            LEFT JOIN batch_holidays bh ON h.date = bh.holiday_date 
                                            WHERE h.date = ? AND bh.id IS NULL", [$date]);
                    if ($holidayCheck) {
                        $displayStatus = 'H';
                    }
                    // If not a holiday, use the original status
                    else {
                        switch (strtoupper($status)) {
                            case 'P':
                            case 'PRESENT':
                                $displayStatus = 'P';
                                break;
                            case 'A':
                            case 'ABSENT':
                                $displayStatus = 'A';
                                break;
                            default:
                                $displayStatus = '';
                        }
                    }
                }
            }
            
            $row[] = $displayStatus;
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}



// Get filter options
$constituencies = fetchAll("SELECT id, name FROM constituencies WHERE status = 'active' ORDER BY name");
$mandals = fetchAll("SELECT id, name, constituency_id FROM mandals WHERE status = 'active' ORDER BY name");
$batches = fetchAll("SELECT id, name, mandal_id FROM batches WHERE status IN ('active', 'completed') ORDER BY status DESC, name");

// Get form filters
$startDate = $_GET['start_date'] ?? date('Y-m-d'); // Current date
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Current date
$selectedConstituency = $_GET['constituency'] ?? '';
$selectedMandal = $_GET['mandal'] ?? '';
$selectedBatch = $_GET['batch'] ?? '';

// Generate report data based on filters
$reportData = generateReportData($startDate, $endDate, $selectedConstituency, $selectedMandal, $selectedBatch);

function generateReportData($startDate, $endDate, $constituency, $mandal, $batch) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($startDate)) {
        $whereConditions[] = "a.attendance_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    
    if (!empty($endDate)) {
        $whereConditions[] = "a.attendance_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    
    if (!empty($constituency)) {
        $whereConditions[] = "b.constituency_id = ?";
        $params[] = $constituency;
        $types .= 'i';
    }
    
    if (!empty($mandal)) {
        $whereConditions[] = "b.mandal_id = ?";
        $params[] = $mandal;
        $types .= 'i';
    }
    
    if (!empty($batch)) {
        $whereConditions[] = "b.batch_id = ?";
        $params[] = $batch;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get attendance summary (supporting both old and new status formats)
    $summaryQuery = "SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'H' THEN 1 ELSE 0 END) as holiday_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    $whereClause";
    
    $summary = fetchRow($summaryQuery, $params, $types);
    
    // Get daily attendance data (supporting both old and new status formats)
    $dailyQuery = "SELECT 
        a.attendance_date,
        COUNT(*) as total_marked,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'H' THEN 1 ELSE 0 END) as holiday_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    $whereClause
    GROUP BY a.attendance_date
    ORDER BY a.attendance_date DESC
    LIMIT 30";
    
    $dailyData = fetchAll($dailyQuery, $params, $types);
    
    // Get constituency-wise summary (supporting both old and new status formats)
    $constituencyQuery = "SELECT 
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    $whereClause
    GROUP BY b.constituency_id, c.name
    ORDER BY present_count DESC";
    
    $constituencyData = fetchAll($constituencyQuery, $params, $types);
    
    // Get mandal-wise summary (supporting both old and new status formats)
    $mandalQuery = "SELECT 
        m.name as mandal_name,
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    $whereClause
    GROUP BY b.mandal_id, m.name, c.name
    ORDER BY present_count DESC";
    
    $mandalData = fetchAll($mandalQuery, $params, $types);
    
    // Get batch-wise summary (supporting both old and new status formats)
    $batchQuery = "SELECT 
        bt.name as batch_name,
        m.name as mandal_name,
        c.name as constituency_name,
        COUNT(*) as total_records,
        SUM(CASE WHEN a.status IN ('present', 'P') THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status IN ('absent', 'A') THEN 1 ELSE 0 END) as absent_count
    FROM attendance a
    JOIN beneficiaries b ON a.beneficiary_id = b.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    $whereClause
    GROUP BY b.batch_id, bt.name, m.name, c.name
    ORDER BY present_count DESC";
    
    $batchData = fetchAll($batchQuery, $params, $types);
    
    return [
        'summary' => $summary,
        'daily' => $dailyData,
        'constituency' => $constituencyData,
        'mandal' => $mandalData,
        'batch' => $batchData
    ];
}
?>

<!-- Report Filters -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i>
            Report Filters
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="constituency" class="form-label">Constituency</label>
                    <select id="constituency" name="constituency" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($constituencies as $constituency): ?>
                            <option value="<?php echo $constituency['id']; ?>" 
                                    <?php echo ($selectedConstituency == $constituency['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($constituency['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="mandal" class="form-label">Mandal</label>
                    <select id="mandal" name="mandal" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($mandals as $mandal): ?>
                            <option value="<?php echo $mandal['id']; ?>" 
                                    data-constituency="<?php echo $mandal['constituency_id']; ?>"
                                    <?php echo ($selectedMandal == $mandal['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mandal['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="batch" class="form-label">Batch</label>
                    <select id="batch" name="batch" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($batches as $batch): ?>
                            <option value="<?php echo $batch['id']; ?>" 
                                    data-mandal="<?php echo $batch['mandal_id']; ?>"
                                    <?php echo ($selectedBatch == $batch['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($batch['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Generate Report
                </button>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['total_records']); ?></div>
                <div class="stats-label">Total Records</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['present_count']); ?></div>
                <div class="stats-label">Present</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['absent_count']); ?></div>
                <div class="stats-label">Absent</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['holiday_count'] ?? 0); ?></div>
                <div class="stats-label">Holidays</div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Rate Row -->
<div class="row">
    <div class="col-md-12">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stats-number">
                    <?php 
                    $attendanceRate = $reportData['summary']['total_records'] > 0 
                        ? round(($reportData['summary']['present_count'] / $reportData['summary']['total_records']) * 100, 1) 
                        : 0;
                    echo $attendanceRate;
                    ?>%
                </div>
                <div class="stats-label">Overall Attendance Rate</div>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-download"></i>
            Export Reports
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="card border">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                        <h5>Attendance Report</h5>
                                                 <p class="text-muted">Daily attendance matrix (CSV format - opens in Excel)</p>
                                                 <button class="btn btn-primary" onclick="exportReport('attendance')">
                             <i class="fas fa-download"></i> Export CSV
                         </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-success mb-3"></i>
                        <h5>Beneficiaries Report</h5>
                        <p class="text-muted">Complete beneficiary information</p>
                        <button class="btn btn-success" onclick="exportReport('beneficiaries')">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h5>Summary Report</h5>
                        <p class="text-muted">Comprehensive summary with constituency, mandal & batch breakdowns</p>
                        <button class="btn btn-info" onclick="exportReport('summary')">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Attendance Chart -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-line"></i>
            Daily Attendance Trend (Last 30 Days)
        </h3>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['daily'])): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Marked</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Holiday</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['daily'] as $day): ?>
                    <?php 
                    $dayAttendanceRate = $day['total_marked'] > 0 
                        ? round(($day['present_count'] / $day['total_marked']) * 100, 1) 
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo formatDate($day['attendance_date'], 'M d, Y'); ?></td>
                        <td><?php echo number_format($day['total_marked']); ?></td>
                        <td><span class="badge badge-success"><?php echo number_format($day['present_count']); ?></span></td>
                        <td><span class="badge badge-danger"><?php echo number_format($day['absent_count']); ?></span></td>
                        <td><span class="badge badge-secondary"><?php echo number_format($day['holiday_count'] ?? 0); ?></span></td>
                        <td>
                            <div class="progress" style="width: 100px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $dayAttendanceRate; ?>%"></div>
                            </div>
                            <small><?php echo $dayAttendanceRate; ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <p class="text-muted">No attendance data found for the selected period.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Constituency-wise Summary -->
<?php if (!empty($reportData['constituency'])): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-map-marker-alt"></i>
            Constituency-wise Summary
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Constituency</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['constituency'] as $constituency): ?>
                    <?php 
                    $constituencyAttendanceRate = $constituency['total_records'] > 0 
                        ? round(($constituency['present_count'] / $constituency['total_records']) * 100, 1) 
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($constituency['constituency_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($constituency['total_records']); ?></td>
                        <td><span class="badge badge-success"><?php echo number_format($constituency['present_count']); ?></span></td>
                        <td><span class="badge badge-danger"><?php echo number_format($constituency['absent_count']); ?></span></td>
                        <td>
                            <div class="progress" style="width: 100px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $constituencyAttendanceRate; ?>%"></div>
                            </div>
                            <small><?php echo $constituencyAttendanceRate; ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mandal-wise Summary -->
<?php if (!empty($reportData['mandal'])): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-building"></i>
            Mandal-wise Summary
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Mandal</th>
                        <th>Constituency</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['mandal'] as $mandal): ?>
                    <?php 
                    $mandalAttendanceRate = $mandal['total_records'] > 0 
                        ? round(($mandal['present_count'] / $mandal['total_records']) * 100, 1) 
                        : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($mandal['mandal_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($mandal['constituency_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($mandal['total_records']); ?></td>
                        <td><span class="badge badge-success"><?php echo number_format($mandal['present_count']); ?></span></td>
                        <td><span class="badge badge-danger"><?php echo number_format($mandal['absent_count']); ?></span></td>
                        <td>
                            <div class="progress" style="width: 100px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $mandalAttendanceRate; ?>%"></div>
                            </div>
                            <small><?php echo $mandalAttendanceRate; ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Batch-wise Summary -->
<?php if (!empty($reportData['batch'])): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users"></i>
            Batch-wise Summary
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Mandal</th>
                        <th>Constituency</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['batch'] as $batch): ?>
                    <?php 
                    $batchAttendanceRate = $batch['total_records'] > 0 
                        ? round(($batch['present_count'] / $batch['total_records']) * 100, 1) 
                        : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($batch['batch_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($batch['mandal_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($batch['constituency_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($batch['total_records']); ?></td>
                        <td><span class="badge badge-success"><?php echo number_format($batch['present_count']); ?></span></td>
                        <td><span class="badge badge-danger"><?php echo number_format($batch['absent_count']); ?></span></td>
                        <td>
                            <div class="progress" style="width: 100px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $batchAttendanceRate; ?>%"></div>
                            </div>
                            <small><?php echo $batchAttendanceRate; ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$inlineJS = "
    // Export function
    function exportReport(type) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const constituency = document.getElementById('constituency').value;
        const mandal = document.getElementById('mandal').value;
        const batch = document.getElementById('batch').value;
        
        let url = '?export=' + type;
        if (startDate) url += '&start_date=' + startDate;
        if (endDate) url += '&end_date=' + endDate;
        if (constituency) url += '&constituency=' + constituency;
        if (mandal) url += '&mandal=' + mandal;
        if (batch) url += '&batch=' + batch;
        
        window.location.href = url;
    }
    
    // Cascade dropdowns
    document.getElementById('constituency').addEventListener('change', function() {
        const constituencyId = this.value;
        const mandalSelect = document.getElementById('mandal');
        const batchSelect = document.getElementById('batch');
        
        // Reset and filter mandal options
        Array.from(mandalSelect.options).forEach(option => {
            if (option.value) {
                option.style.display = constituencyId && option.dataset.constituency != constituencyId ? 'none' : 'block';
            }
        });
        
        mandalSelect.value = '';
        batchSelect.value = '';
        
        // Reset batch options
        Array.from(batchSelect.options).forEach(option => {
            option.style.display = 'block';
        });
    });
    
    document.getElementById('mandal').addEventListener('change', function() {
        const mandalId = this.value;
        const batchSelect = document.getElementById('batch');
        
        Array.from(batchSelect.options).forEach(option => {
            if (option.value) {
                option.style.display = mandalId && option.dataset.mandal != mandalId ? 'none' : 'block';
            }
        });
        
        batchSelect.value = '';
    });
";

require_once '../includes/footer.php';
?>
