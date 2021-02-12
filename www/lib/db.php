<?php
class DB {
    public $conn;
    public $type;
    
    function connect($cfg) {
        $this->type = $cfg['type'];

        if($cfg['type'] == 'sqlite') {
            $this->conn = new SQLite3($cfg['file']);
        } elseif ($cfg['type'] == 'mysql') {

            $this->conn = mysqli_connect($cfg['server'], $cfg['username'], $cfg['password'],$cfg['database'], $cfg['port']);
            if (!$this->conn) {
                $this->message = 'Cannot connect to mySQL';
                return 0;
            } else {
                $this->message = 'Success';
                return 1;
            }

        } else {
            $this->message = 'Unknown database type - ' . $cfg['type'];
            return 0;
        }
    }

    # create a very basic table with a uniqueId identifier field if it doesn't already exist
    function create_table($tablename) {
        if($this->type == 'sqlite') {
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $tablename . ' ( id_' . $tablename . ' INTEGER  PRIMARY KEY)';

            $result = $this->conn->query($sql);
            if ($result) {
                $this->message = 'Success';
                return 1;
            } else {
                $this->message =  'Unable to create table -- ' . $this->conn->lastErrorMsg();
                return 0;
            }

        } elseif ($this->type == 'mysql') {
            if (!mysqli_query($this->conn, 'select 1 from `' . $tablename . '`')) {
                $sql = 'CREATE TABLE `' . $tablename . '` (
                    `_id_' . $tablename . '` bigint auto_increment primary key,
                    `_createdon`    datetime,
                    `_createdby`    varchar(200),
                    `_createdip`    varchar(20),
                    `_updatedon`    datetime,
                    `_updatedby`    varchar(200),
                    `_updatedip`    varchar(20)
                )';
                if(mysqli_query($this->conn,$sql)) {
                    $this->message = 'Success';
                    return 1;
                } else {
                    $this->message =  'Unable to create table -- ' . mysqli_error($this->conn);
                    return 0;
                }
            } else {
                $this->message = 'Table already exists';
                return 0;
            }
            
        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }

    }

    function create_field($tablename,$fieldname,$fieldtype) {
        $types = array(
            'text' => array(
                'mysql'     => 'VARCHAR(200)',
                'sqlite'    => 'VARCHAR(200)',
                'postgres'  => 'VARCHAR(200)'
            ),
            'textarea' => array(
                'mysql'     => 'TEXT',
                'sqlite'    => 'TEXT',
                'postgres'  => 'TEXT'
            ),
        );

        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {   
            if (!mysqli_query($this->conn, 'select `' . $fieldname . '` from `' . $tablename . '` limit 1')) {
                // if not, then add it
                if(!mysqli_query($this->conn,'ALTER TABLE `' . $tablename . '` ADD COLUMN `' . $fieldname . '` ' . $types[$fieldtype][$this->type])) {
                    $this->message = "ERROR creating field $tablename.$fieldname -- " . mysqli_error($this->conn);;
                    return 0;
                } else {
                    $this->message = 'Success';
                    return 1;
                }

            } else {
                $this->message = 'Field already exists';
                return 1;
            }
        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function delete($tablename,$key) {
        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {
            $sql = 'DELETE FROM `' . $tablename . '` WHERE ';
            foreach ($key as $field => $value) {
                $sql .= '`' . $field . '` = \'' . mysqli_escape_string($this->conn, $value) . '\' AND ';
            }
            $sql .= '1 = 1';
            if(mysqli_query($this->conn,$sql)) {
                $this->message = 'Success';
                return 1;
            } else {
                $this->message = 'Unable to delete data -- '. mysqli_error($this->conn);
                return 0;
            }

        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function select($tablename,$key) {
        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {

            if (is_array($key)) {
                # -- this is an array, so we treat it like keys
                $sql = 'SELECT * FROM `' . $tablename . '`';
                if($key) {
                    $sql .= ' WHERE ';
                    foreach ($key as $field => $value) {
                        $sql .= '`' . $field . '` = \'' . mysqli_escape_string($this->conn, $value) . '\' AND ';
                    }
                    $sql .= ' 1 = 1';
                }

            } else {
                # -- this is not an array, so we assume it's raw SQL -- not preferred, but hey, sometimes we may need this
                $sql = $key;
            }

            if($result = mysqli_query($this->conn,$sql)) {
                $this->message = 'Success';
                $out = mysqli_fetch_all($result,MYSQLI_ASSOC);
                mysqli_free_result($result);
                return $out;
            } else {
                $this->message = 'Unable to select data -- '. mysqli_error($this->conn);
                return 0;
            }

        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function update($tablename,$key,$data) {
        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {

            $sql = 'UPDATE `' . $tablename . '` SET ';
            $data['_updatedip'] = $_SERVER['REMOTE_ADDR'];

            foreach ($data as $field => $value) {
                $sql .= '`' . $field . '` = \'' . mysqli_escape_string($this->conn, $value) . '\', ';
            }
            $sql .= '`_updatedon` = CURRENT_TIMESTAMP';

            if($key) {
                $sql .= ' WHERE ';
                foreach ($key as $field => $value) {
                    $sql .= '`' . $field . '` = \'' . mysqli_escape_string($this->conn, $value) . '\' AND ';
                }
                $sql .= ' 1 = 1';
            }

            if(mysqli_query($this->conn,$sql)) {
                if(mysqli_affected_rows($this->conn) == 0) {
                    # -- if we can't update anything, we will add the data into the table
                    return $this->insert($tablename,array_merge($data,$key));
                }

                $this->message = 'Success';
                return 0;
            } else {
                $this->message = 'Unable to update data -- '. mysqli_error($this->conn);
                return 0;
            }
    
        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function housekeeping($tablename,$age) {
        # specify the age in seconds, and any record older than that will be deleted
        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {
            $sql = 'DELETE FROM `' . $tablename . '` WHERE TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP,`_updatedon`)) > \'' . mysqli_escape_string($this->conn, $age) . '\';';
            if(mysqli_query($this->conn,$sql)) {
                $this->message = 'Success';
                return 1;
            } else {
                $this->message = 'Unable to perform housekeeping -- '. mysqli_error($this->conn);
                return 0;
            }
        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function insert($tablename,$data) {
        # TODO - add createdby once we have authentication
        if($this->type == 'sqlite') {
            $this->message = 'TODO';
            return 0;
        } elseif ($this->type == 'mysql') {
            $fields = '';
            $values = '';

            $data['_createdip'] = $_SERVER['REMOTE_ADDR'];
            $data['_updatedip'] = $_SERVER['REMOTE_ADDR'];
            foreach ($data as $field => $value) {
                $fields .= '`' . $field . '`,';
                $values .= '\'' . mysqli_escape_string($this->conn, $value) . '\',';
            }

            $sql = 'INSERT INTO `' . $tablename . '` (' . $fields . '`_createdon`,`_updatedon`) VALUES(' . $values . 'CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);';
            
            if(mysqli_query($this->conn,$sql)) {
                $this->message = 'Success';
                return 1;
            } else {
                $this->message = 'Unable to insert data -- '. mysqli_error($this->conn);
                return 0;
            }
        } else {
            $this->message = 'Unknown database type - ' . $this->type;
            return 0;
        }
    }

    function create_schema($schema) {
        foreach($schema as $tablename => $table) {
            if($this->create_table($tablename)) {
                foreach($table as $fieldname => $fielddef) {
                     $this->create_field($tablename,$fieldname,$fielddef['type']);
                }
            }
        }   
    }
}