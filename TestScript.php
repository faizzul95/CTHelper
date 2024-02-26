<?php

if (file_exists('vendor/autoload.php'))
    include_once 'vendor/autoload.php';

use CT\Database;
// use CT\Helpers\Input;

// FUNCTION FOR DEBUG

function dump()
{
    array_map(function ($param) {
        echo '<pre>';
        var_dump($param);
        echo '</pre>';
    }, func_get_args());
}

function dd()
{
    array_map(function ($param) {
        echo '<pre>';
        print_r($param);
        echo '</pre>';
    }, func_get_args());
    die;
}

function runTest($scriptNames)
{
    $results = [];

    if (!is_array($scriptNames)) {
        return ['error' => 'Script names must be provided as an array'];
    }

    foreach ($scriptNames as $scriptName) {
        // Check if script name is a string
        if (!is_string($scriptName)) {
            $results[] = ['error' => 'Invalid script name'];
            continue;
        }

        // Start the timer
        $start_time = microtime(true);

        // Execute the test script
        try {
            $result = call_user_func($scriptName);
            $execution_time = microtime(true) - $start_time;
            $results[$scriptName] = [
                'execution_time' => number_format($execution_time, 6),
                'result' => json_encode($result)
            ];
        } catch (Exception $e) {
            $results[$scriptName] = ['error' => $e->getMessage()];
        }
    }

    return $results;
}

// TEST SCRIPT

$db = new Database('mysql','localhost', 'root', '', 'plant_db');

/**
 * Script 1     : Add another connection & connect to connection.
 * Expectation  : Verify that adding another connection and connecting to it succeeds.
 * Result       : Pass.
 */
$db->addConnection('slave', array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'db' => 'xxx',
    'charset' => 'utf8mb4'
));

/**
 * Script 2     : Connect to slave table.
 * Expectation  : Ensure successful connection to the slave table.
 * Result       : Pass.
 */
$db->connect('slave');

/**
 * Script 3     : Test the disconnect() function.
 * Expectation  : Validate that disconnecting from the connection and optionally removing the setting works as expected.
 * Result       : Pass.
 */
$db->disconnect('slave', false);

/**
 * Script 4     : Test the reset() function.
 * Expectation  : Confirm that resetting all variables works correctly.
 * Result       : Pass.
 */
$db->reset();

/**
 * Script 5     : Test the instantiation of the Database class.
 * Expectation  : Ensure that the Database class can be instantiated using the getInstance() method.
 * Result       : Pass.
 */
$instanceDb = Database::getInstance();

// QUERY SELECT STATEMENT

/**
 * Script 1: Retrieve the SQL query string for selecting data from the 'users' table.
 * Expectation: The function should return the query string.
 * Remark: -
 * Result: Pass.
 */
function script1()
{
    $db = Database::getInstance();
    return $db->table('users')->toSql();
}

/**
 * Script 2     : Retrieve all data from the 'users' table.
 * Expectation  : The function should return all the data from the 'users' table.
 * Remark       : -
 * Result       : Pass.
 */
function script2()
{
    $db = Database::getInstance();
    return $db->table('users')->get();
}

/**
 * Script 3     : Retrieve only one data entry from the 'users' table.
 * Expectation  : The function should return only one data entry from the 'users' table.
 * Remark       : -
 * Result       : Pass.
 */
function script3()
{
    $db = Database::getInstance();
    return $db->table('users')->fetch();
}

/**
 * Script 4     : Retrieve data from the 'users' table with a specific limit.
 * Expectation  : The function should return data from the 'users' table with a limit of 3 entries.
 * Remark       : -
 * Result       : Pass.
 */
function script4()
{
    $db = Database::getInstance();
    return $db->table('users')->limit(3)->get();
}

/**
 * Script 5     : Retrieve data from the 'users' table with a specific limit and ordering.
 * Expectation  : The function should return data from the 'users' table with a limit of 3 entries, ordered by the 'name' column in descending order.
 * Remark       : -
 * Result       : Pass.
 */
function script5()
{
    $db = Database::getInstance();
    return $db->table('users')->orderBy('name', 'asc')->limit(3)->get();
    // return $db->table('users')->orderBy([['name', 'desc'], ['user_status', 'desc']])->limit(3)->get();
}

/**
 * Script 6     : Retrieve specific fields from the 'users' table for a particular user.
 * Expectation  : The function should fetch the 'id' and 'name' fields for the user with an ID of 2.
 * Remark       : -
 * Result       : Pass.
 */
function script6()
{
    $db = Database::getInstance();
    return $db->table('users')->select('id,name')->where('id', 2)->fetch();
    // return $db->table('users')->select(['id', 'name'])->where(['id' => 2])->fetch();
}

/**
 * Script 7     : Retrieve paginated data from the 'school_users' table where the 'applicant_status' is '13'.
 * Expectation  : The function should retrieve paginated data from the 'school_users' table where the 'applicant_status' is '13'.
 * Remark       : The function connects to the 'slave' database before executing the query and disconnects afterward.
 * Result       : Pass.
 */
function script7()
{
    $db = Database::getInstance();

    $db->connect('slave');
    $result = $db->connection('slave')->table('school_users')->select('id,user_id')->where('applicant_status', '13')->paginate(1);
    $db->disconnect('slave');

    return $result;
}

/**
 * Script 8     : Retrieve paginated data from the 'users' table with eager loading of 'profile' and 'schools'.
 * Expectation  : The function should retrieve paginated data from the 'users' table with eager loading of 'profile' and 'schools'.
 * Remark       : The function connects to the 'slave' database before executing the query and disconnects afterward.
 * Result       : Pass.
 */
function script8()
{
    $db = Database::getInstance();

    $db->connect('slave');

    $result = $db->connection('slave')
        ->table('users')
        ->select('id,name,nickname')
        ->with('profile', 'school_users', 'user_id', 'id', function ($db) {
            $db->select('id,user_id,school_id,school_profile_id')
                ->with('schools', 'schools', 'id', 'school_id', function ($db) {
                    $db->select('id,name,contact_name');
                });
        })
        ->paginate(1);

    $db->disconnect('slave');

    return $result;
}

/**
 * Script 9     : Retrieve paginated user data with school information.
 * Expectation  : Retrieve users along with their associated school information in a paginated manner.
 * Remark       : This script queries the 'users' table and joins it with 'school_users' and 'schools' tables to fetch user data along with associated school details.
 * Result       : Pass.
 */
function script9()
{
    $db = Database::getInstance();

    $db->connect('slave');

    $result = $db->connection('slave')
        ->table('users')
        ->select('users.id,users.name,users.nickname,school_users.school_profile_id,schools.name AS company_name')
        ->join('school_users', 'users.id=school_users.user_id', 'left')
        ->join('schools', 'school_users.school_id=schools.id', 'left')
        ->paginate(1);

    $db->disconnect('slave');

    return $result;
}

/**
 * Script 10    : Insert a single user record.
 * Expectation  : Insert a new user record into the 'users' table.
 * Remark       : This script inserts a single user record into the 'users' table with specified attributes.
 * Result       : Pass.
 */
function script10()
{
    $db = Database::getInstance();
    return $db->insert('users', [
        'name' => 'Testing2',
        'user_preferred_name' => 't2',
        'email' => 'testing4@user.com',
    ]);
}

/**
 * Script 11    : Batch insert multiple user records.
 * Expectation  : Insert multiple user records into the 'users' table in a single batch.
 * Remark       : This script attempts to insert multiple user records into the 'users' table in a single batch. It provides an array of user data to be inserted, with each element representing a user record.
 * Result       : Pass.
 */
function script11()
{
    $db = Database::getInstance();
    return $db->insertBatch('users', [
        [
            'name' => 'Batch 4',
            'user_preferred_name' => 'B1',
            'email' => 'batch1@user.com',
            'user_gender' => '1'
        ],
        [
            'name' => 'Batch 5',
            'user_preferred_name' => 'B2',
            'email' => 'batch2@user.com',
            'user_gender' => '1'

        ],
        [
            'name' => 'Batch 6',
            'user_preferred_name' => 'B3',
            'email' => 'batch3@user.com',
            'user_genderssss' => '1' // Will be removed upon sanitizing the column since this column does not exist in the table.
        ]
    ]);
}

/**
 * Script 12    : Fetch user profile data with associated role and permissions.
 * Expectation  : Retrieve user profile data along with the associated role and permissions.
 * Remark       : This script fetches user profile data for a specific user along with the associated role and permissions. It constructs a complex query using the database builder to efficiently fetch the required data and their relations.
 * Result       : Pass.
 */
function script12()
{
    $db = Database::getInstance();
    return $db->table('user_profile')
        ->select('id,user_id,role_id,is_main,profile_status')
        ->where('user_id', 1)
        ->where('is_main', 1)
        ->withOne('roles', 'master_roles', 'id', 'role_id', function ($db) {
            $db->select('id,role_name,role_status')
                ->where('role_status', 1)
                ->with('permission', 'system_permission', 'role_id', 'id', function ($db) {
                    $db->select('id,role_id,abilities_id,forbidden')
                        ->where('forbidden', 0)
                        ->withOne('abilities', 'system_abilities', 'id', 'abilities_id', function ($db) {
                            $db->select('id,title');
                        });
                });
        })
        ->fetch();;
}

/**
 * Script 13    : Fetch user profile data with associated role and permissions using raw SQL queries.
 * Expectation  : Retrieve user profile data along with the associated role and permissions using raw SQL queries.
 * Remark       : This script fetches user profile data for a specific user along with the associated role and permissions using raw SQL queries. 
 *                It constructs SQL queries directly for efficient retrieval of the required data and their relations.
 * Result       : Pass.
 */
function script13()
{
    $db = Database::getInstance();
    $profile = $db->rawQuery('SELECT id,user_id,role_id,is_main,profile_status FROM user_profile WHERE user_id = ? AND is_main = ?', [1, 1], 'fetch');
    $profile['roles'] = $db->rawQuery('SELECT id,role_name,role_status FROM master_roles WHERE id = ? AND role_status = ?', [$profile['role_id'], 1], 'fetch');
    $profile['roles']['permission'] = $db->rawQuery('SELECT id,role_id,abilities_id,forbidden FROM system_permission WHERE forbidden = ?', [0]);

    return $profile;
}

/**
 * Script 14    : Retrieve user profile information with associated files, roles, and permissions.
 * Expectation  : Retrieve user profile information along with associated files, roles, and permissions.
 * Remark       : This script fetches user profile information including user ID, role ID, and other details. It also includes the associated profile avatar and header files, 
 *                along with the roles associated with the user and their permissions. 
 *                The script utilizes eager loading to efficiently fetch related data.
 * Result       : Pass.
 */
function script14()
{
    $db = Database::getInstance();
    $users = $db->table('user_profile')
        ->select('id,user_id,role_id,is_main,profile_status')
        ->where('user_id', 1)
        ->where('is_main', 1)
        ->with('profile_avatar', 'entity_files', 'entity_id', 'id', function ($db) {
            $db->select('id,files_path,files_disk_storage,files_path_is_url,files_description,entity_file_type')
                ->where('entity_type', 'User_model')
                ->where('entity_file_type', 'PROFILE_PHOTO');
        })
        ->withOne('profile_header', 'entity_files', 'entity_id', 'id', function ($db) {
            $db->select('id,files_path,files_disk_storage,files_path_is_url,files_description,entity_file_type')
                ->where('entity_type', 'User_model')
                ->where('entity_file_type', 'PROFILE_HEADER_PHOTO');
        })
        ->withOne('roles', 'master_roles', 'id', 'role_id', function ($db) {
            $db->select('id,role_name,role_status')->where('role_status', 1)
                ->with('permission', 'system_permission', 'role_id', 'id', function ($db) {
                    $db->select('id,role_id,abilities_id,forbidden')->where('forbidden', 0)
                        ->withOne('abilities', 'system_abilities', 'id', 'abilities_id', function ($db) {
                            $db->select('id,title');
                        });
                });
        })
        ->fetch();

    return $users;
}

/**
 * Script 15    : Test XSS filtering before saving to the database.
 * Expectation  : Ensure that XSS (Cross-Site Scripting) attacks are prevented by converting potentially harmful scripts.
 * Remark       : The 'secure' tag is set to 'true' to test if XSS will be converted before saving to the database.
 * Result       : Pass.
 */
function script15()
{
    $db = Database::getInstance();
    return $db->insert('plant', [
        'plant_code' => 'T0006',
        'plant_name' => "<IMG SRC=j&#X41vascript:alert('test2')>", // XSS Script
        'plant_family' => 'Akar',
        'plant_botany_name' => "<b onmouseover=alert('Wufff!')>click me!</b>", // XSS Script
        'plant_type_id' => '2',
        'plant_category_id' => 1,
        'plantcategory_id343242' => 1, // This column doesn't exist and will be removed upon insertion
    ]);
}

/**
 * Script 16    : Test XSS filtering with secure tag set to false.
 * Expectation  : Evaluate whether XSS filtering is disabled when the secure tag is set to false using secureInput() function.
 * Remark       : The 'secure' tag is set to false to test if XSS filtering is bypassed.
 *                Additionally, it removes the invalid column 'plantcategory_id343242'.
 * Result       : Pass.
 */
function script16()
{
    $db = Database::getInstance();
    return $db->secureInput(false)->insert('plant', [
        'plant_code' => 'T0007',
        'plant_name_noxist' => "<IMG SRC=j&#X41vascript:alert('test2')>", // XSS Script
        'plant_name' => "<IMG SRC=j&#X41vascript:alert('test2')>", // XSS Script
        'plant_family' => 'Pokok',
        'plant_botany_name' => "<b onmouseover=alert('Wufff!')>click me!</b>", // XSS Script
        'plant_type_id' => '3',
        'plant_category_id' => 3,
        'plantcategory_id343242' => 4, // This column doesn't exist and will be removed upon insertion
    ]);
}


/**
 * Script 17    : Test updating data in the 'plant' table with XSS vulnerability and invalid column removal.
 * Expectation  : Ensure that data can be updated in the 'plant' table while preventing XSS attacks and removing invalid columns.
 * Remark       : This script updates plant data with potential XSS vulnerability in 'plant_name' and 'plant_botany_name' fields.
 *                Additionally, it removes the invalid column 'plantcategory_id343242'.
 * Result       : Pass.
 */
function script17()
{
    $db = Database::getInstance();
    return $db->update(
        'plant',
        [
            'plant_code' => 'T0007-1',
            'plant_name' => "<IMG SRC=j&#X41vascript:alert('test3')>", // XSS Script
            'plant_family' => 'Akar',
            'plant_botany_name' => "<b onmouseover=alert('Wufff!')>click me!</b>", // XSS Script
            'plant_type_id' => '1',
            'plant_category_id' => 6,
            'plantcategory_id343242' => 10, // This column doesn't exist and will be removed upon insertion
        ],
        ['id' => 9] // also can use as string conditions 'id = 6' (Only use if has advanced conditions)
    );
}

/**
 * Script 18    : Test deleting a record from the 'plant' table.
 * Expectation  : Verify that the record with ID 7 is successfully deleted from the 'plant' table.
 * Remark       : This script specifically targets the deletion of a record with ID 7.
 * Result       : Pass.
 */
function script18()
{
    $db = Database::getInstance();
    return $db->delete('plant', ['id' => 9]);
}

/**
 * Script 19    : Get SQL query for selecting users with first_login equals 1 and id greater than 1.
 * Expectation  : To retrieve SQL query for fetching users with specific conditions.
 * Remark       : 
 * Result       : -
 */
function script19()
{
    $db = Database::getInstance();
    return $db->table('users')
        ->where('first_login', 1)
        ->having('id', 1, '>')
        ->toSql();
}

/**
 * Script 20    : Get full SQL query with complex conditions involving EXISTS, IN, and OR clauses.
 * Expectation  : To retrieve a complex SQL query involving various conditions.
 * Remark       : 
 * Result       : -.
 */
function script20()
{
    $db = Database::getInstance();
    return $db->table('users')
        ->where('first_login', 'EXISTS (SELECT AVG(column2) FROM table1 WHERE column3 = t1.column3)', '')
        ->whereIn('first_login', '(SELECT AVG(column2) FROM table1 WHERE column3 = t1.column5)')
        ->orWhereNotIn('status', '(SELECT * FROM table1 WHERE column3 = t1.column5)')
        ->getFullSql();
}

dd(runTest(
    [
        // 'script1',
        // 'script2',
        // 'script3',
        // 'script4',
        // 'script5',
        // 'script6',
        // 'script7',
        // 'script8',
        // 'script9',
        // 'script10',
        // 'script11'
        // 'script12',
        // 'script13',
        // 'script14',
        // 'script15',
        // 'script16',
        // 'script17',
        'script18',
        // 'script19',
        // 'script20',
    ]
));
