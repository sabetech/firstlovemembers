<?php

namespace ChurchCRM\Base;

use \DateTime;
use \Exception;
use \PDO;
use ChurchCRM\Event as ChildEvent;
use ChurchCRM\EventAttend as ChildEventAttend;
use ChurchCRM\EventAttendQuery as ChildEventAttendQuery;
use ChurchCRM\EventQuery as ChildEventQuery;
use ChurchCRM\Group as ChildGroup;
use ChurchCRM\GroupQuery as ChildGroupQuery;
use ChurchCRM\KioskAssignment as ChildKioskAssignment;
use ChurchCRM\KioskAssignmentQuery as ChildKioskAssignmentQuery;
use ChurchCRM\Map\EventAttendTableMap;
use ChurchCRM\Map\EventTableMap;
use ChurchCRM\Map\KioskAssignmentTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base class that represents a row from the 'events_event' table.
 *
 * This contains events
 *
 * @package    propel.generator.ChurchCRM.Base
 */
abstract class Event implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\ChurchCRM\\Map\\EventTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the event_id field.
     *
     * @var        int
     */
    protected $event_id;

    /**
     * The value for the event_type field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $event_type;

    /**
     * The value for the event_title field.
     *
     * Note: this column has a database default value of: ''
     * @var        string
     */
    protected $event_title;

    /**
     * The value for the event_desc field.
     *
     * @var        string
     */
    protected $event_desc;

    /**
     * The value for the event_text field.
     *
     * @var        string
     */
    protected $event_text;

    /**
     * The value for the event_start field.
     *
     * @var        DateTime
     */
    protected $event_start;

    /**
     * The value for the event_end field.
     *
     * @var        DateTime
     */
    protected $event_end;

    /**
     * The value for the inactive field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $inactive;

    /**
     * The value for the event_typename field.
     *
     * Note: this column has a database default value of: ''
     * @var        string
     */
    protected $event_typename;

    /**
     * The value for the event_grpid field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $event_grpid;

    /**
     * @var        ChildGroup
     */
    protected $aGroup;

    /**
     * @var        ObjectCollection|ChildEventAttend[] Collection to store aggregation of ChildEventAttend objects.
     */
    protected $collEventAttends;
    protected $collEventAttendsPartial;

    /**
     * @var        ObjectCollection|ChildKioskAssignment[] Collection to store aggregation of ChildKioskAssignment objects.
     */
    protected $collKioskAssignments;
    protected $collKioskAssignmentsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildEventAttend[]
     */
    protected $eventAttendsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildKioskAssignment[]
     */
    protected $kioskAssignmentsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->event_type = 0;
        $this->event_title = '';
        $this->inactive = 0;
        $this->event_typename = '';
        $this->event_grpid = 0;
    }

    /**
     * Initializes internal state of ChurchCRM\Base\Event object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Event</code> instance.  If
     * <code>obj</code> is an instance of <code>Event</code>, delegates to
     * <code>equals(Event)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|Event The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        $cls = new \ReflectionClass($this);
        $propertyNames = [];
        $serializableProperties = array_diff($cls->getProperties(), $cls->getProperties(\ReflectionProperty::IS_STATIC));

        foreach($serializableProperties as $property) {
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }

    /**
     * Get the [event_id] column value.
     *
     * @return int
     */
    public function getId()
    {
        return $this->event_id;
    }

    /**
     * Get the [event_type] column value.
     *
     * @return int
     */
    public function getType()
    {
        return $this->event_type;
    }

    /**
     * Get the [event_title] column value.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->event_title;
    }

    /**
     * Get the [event_desc] column value.
     *
     * @return string
     */
    public function getDesc()
    {
        return $this->event_desc;
    }

    /**
     * Get the [event_text] column value.
     *
     * @return string
     */
    public function getText()
    {
        return $this->event_text;
    }

    /**
     * Get the [optionally formatted] temporal [event_start] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getStart($format = NULL)
    {
        if ($format === null) {
            return $this->event_start;
        } else {
            return $this->event_start instanceof \DateTimeInterface ? $this->event_start->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [event_end] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getEnd($format = NULL)
    {
        if ($format === null) {
            return $this->event_end;
        } else {
            return $this->event_end instanceof \DateTimeInterface ? $this->event_end->format($format) : null;
        }
    }

    /**
     * Get the [inactive] column value.
     *
     * @return int
     */
    public function getInActive()
    {
        return $this->inactive;
    }

    /**
     * Get the [event_typename] column value.
     *
     * @return string
     */
    public function getTypeName()
    {
        return $this->event_typename;
    }

    /**
     * Get the [event_grpid] column value.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->event_grpid;
    }

    /**
     * Set the value of [event_id] column.
     *
     * @param int $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->event_id !== $v) {
            $this->event_id = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [event_type] column.
     *
     * @param int $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setType($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->event_type !== $v) {
            $this->event_type = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_TYPE] = true;
        }

        return $this;
    } // setType()

    /**
     * Set the value of [event_title] column.
     *
     * @param string $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setTitle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->event_title !== $v) {
            $this->event_title = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_TITLE] = true;
        }

        return $this;
    } // setTitle()

    /**
     * Set the value of [event_desc] column.
     *
     * @param string $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setDesc($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->event_desc !== $v) {
            $this->event_desc = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_DESC] = true;
        }

        return $this;
    } // setDesc()

    /**
     * Set the value of [event_text] column.
     *
     * @param string $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setText($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->event_text !== $v) {
            $this->event_text = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_TEXT] = true;
        }

        return $this;
    } // setText()

    /**
     * Sets the value of [event_start] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setStart($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->event_start !== null || $dt !== null) {
            if ($this->event_start === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->event_start->format("Y-m-d H:i:s.u")) {
                $this->event_start = $dt === null ? null : clone $dt;
                $this->modifiedColumns[EventTableMap::COL_EVENT_START] = true;
            }
        } // if either are not null

        return $this;
    } // setStart()

    /**
     * Sets the value of [event_end] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTimeInterface value.
     *               Empty strings are treated as NULL.
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setEnd($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->event_end !== null || $dt !== null) {
            if ($this->event_end === null || $dt === null || $dt->format("Y-m-d H:i:s.u") !== $this->event_end->format("Y-m-d H:i:s.u")) {
                $this->event_end = $dt === null ? null : clone $dt;
                $this->modifiedColumns[EventTableMap::COL_EVENT_END] = true;
            }
        } // if either are not null

        return $this;
    } // setEnd()

    /**
     * Set the value of [inactive] column.
     *
     * @param int $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setInActive($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->inactive !== $v) {
            $this->inactive = $v;
            $this->modifiedColumns[EventTableMap::COL_INACTIVE] = true;
        }

        return $this;
    } // setInActive()

    /**
     * Set the value of [event_typename] column.
     *
     * @param string $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setTypeName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->event_typename !== $v) {
            $this->event_typename = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_TYPENAME] = true;
        }

        return $this;
    } // setTypeName()

    /**
     * Set the value of [event_grpid] column.
     *
     * @param int $v new value
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function setGroupId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->event_grpid !== $v) {
            $this->event_grpid = $v;
            $this->modifiedColumns[EventTableMap::COL_EVENT_GRPID] = true;
        }

        if ($this->aGroup !== null && $this->aGroup->getId() !== $v) {
            $this->aGroup = null;
        }

        return $this;
    } // setGroupId()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->event_type !== 0) {
                return false;
            }

            if ($this->event_title !== '') {
                return false;
            }

            if ($this->inactive !== 0) {
                return false;
            }

            if ($this->event_typename !== '') {
                return false;
            }

            if ($this->event_grpid !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : EventTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : EventTableMap::translateFieldName('Type', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_type = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : EventTableMap::translateFieldName('Title', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_title = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : EventTableMap::translateFieldName('Desc', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_desc = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : EventTableMap::translateFieldName('Text', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_text = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : EventTableMap::translateFieldName('Start', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->event_start = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : EventTableMap::translateFieldName('End', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->event_end = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : EventTableMap::translateFieldName('InActive', TableMap::TYPE_PHPNAME, $indexType)];
            $this->inactive = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : EventTableMap::translateFieldName('TypeName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_typename = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : EventTableMap::translateFieldName('GroupId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->event_grpid = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 10; // 10 = EventTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\ChurchCRM\\Event'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aGroup !== null && $this->event_grpid !== $this->aGroup->getId()) {
            $this->aGroup = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(EventTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildEventQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aGroup = null;
            $this->collEventAttends = null;

            $this->collKioskAssignments = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Event::setDeleted()
     * @see Event::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(EventTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildEventQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($this->alreadyInSave) {
            return 0;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(EventTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                EventTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aGroup !== null) {
                if ($this->aGroup->isModified() || $this->aGroup->isNew()) {
                    $affectedRows += $this->aGroup->save($con);
                }
                $this->setGroup($this->aGroup);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
            }

            if ($this->eventAttendsScheduledForDeletion !== null) {
                if (!$this->eventAttendsScheduledForDeletion->isEmpty()) {
                    \ChurchCRM\EventAttendQuery::create()
                        ->filterByPrimaryKeys($this->eventAttendsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->eventAttendsScheduledForDeletion = null;
                }
            }

            if ($this->collEventAttends !== null) {
                foreach ($this->collEventAttends as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->kioskAssignmentsScheduledForDeletion !== null) {
                if (!$this->kioskAssignmentsScheduledForDeletion->isEmpty()) {
                    foreach ($this->kioskAssignmentsScheduledForDeletion as $kioskAssignment) {
                        // need to save related object because we set the relation to null
                        $kioskAssignment->save($con);
                    }
                    $this->kioskAssignmentsScheduledForDeletion = null;
                }
            }

            if ($this->collKioskAssignments !== null) {
                foreach ($this->collKioskAssignments as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[EventTableMap::COL_EVENT_ID] = true;
        if (null !== $this->event_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . EventTableMap::COL_EVENT_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(EventTableMap::COL_EVENT_ID)) {
            $modifiedColumns[':p' . $index++]  = 'event_id';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TYPE)) {
            $modifiedColumns[':p' . $index++]  = 'event_type';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TITLE)) {
            $modifiedColumns[':p' . $index++]  = 'event_title';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_DESC)) {
            $modifiedColumns[':p' . $index++]  = 'event_desc';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TEXT)) {
            $modifiedColumns[':p' . $index++]  = 'event_text';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_START)) {
            $modifiedColumns[':p' . $index++]  = 'event_start';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_END)) {
            $modifiedColumns[':p' . $index++]  = 'event_end';
        }
        if ($this->isColumnModified(EventTableMap::COL_INACTIVE)) {
            $modifiedColumns[':p' . $index++]  = 'inactive';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TYPENAME)) {
            $modifiedColumns[':p' . $index++]  = 'event_typename';
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_GRPID)) {
            $modifiedColumns[':p' . $index++]  = 'event_grpid';
        }

        $sql = sprintf(
            'INSERT INTO events_event (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'event_id':
                        $stmt->bindValue($identifier, $this->event_id, PDO::PARAM_INT);
                        break;
                    case 'event_type':
                        $stmt->bindValue($identifier, $this->event_type, PDO::PARAM_INT);
                        break;
                    case 'event_title':
                        $stmt->bindValue($identifier, $this->event_title, PDO::PARAM_STR);
                        break;
                    case 'event_desc':
                        $stmt->bindValue($identifier, $this->event_desc, PDO::PARAM_STR);
                        break;
                    case 'event_text':
                        $stmt->bindValue($identifier, $this->event_text, PDO::PARAM_STR);
                        break;
                    case 'event_start':
                        $stmt->bindValue($identifier, $this->event_start ? $this->event_start->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'event_end':
                        $stmt->bindValue($identifier, $this->event_end ? $this->event_end->format("Y-m-d H:i:s.u") : null, PDO::PARAM_STR);
                        break;
                    case 'inactive':
                        $stmt->bindValue($identifier, $this->inactive, PDO::PARAM_INT);
                        break;
                    case 'event_typename':
                        $stmt->bindValue($identifier, $this->event_typename, PDO::PARAM_STR);
                        break;
                    case 'event_grpid':
                        $stmt->bindValue($identifier, $this->event_grpid, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = EventTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getType();
                break;
            case 2:
                return $this->getTitle();
                break;
            case 3:
                return $this->getDesc();
                break;
            case 4:
                return $this->getText();
                break;
            case 5:
                return $this->getStart();
                break;
            case 6:
                return $this->getEnd();
                break;
            case 7:
                return $this->getInActive();
                break;
            case 8:
                return $this->getTypeName();
                break;
            case 9:
                return $this->getGroupId();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {

        if (isset($alreadyDumpedObjects['Event'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Event'][$this->hashCode()] = true;
        $keys = EventTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getType(),
            $keys[2] => $this->getTitle(),
            $keys[3] => $this->getDesc(),
            $keys[4] => $this->getText(),
            $keys[5] => $this->getStart(),
            $keys[6] => $this->getEnd(),
            $keys[7] => $this->getInActive(),
            $keys[8] => $this->getTypeName(),
            $keys[9] => $this->getGroupId(),
        );
        if ($result[$keys[5]] instanceof \DateTimeInterface) {
            $result[$keys[5]] = $result[$keys[5]]->format('c');
        }

        if ($result[$keys[6]] instanceof \DateTimeInterface) {
            $result[$keys[6]] = $result[$keys[6]]->format('c');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aGroup) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'group';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'group_grp';
                        break;
                    default:
                        $key = 'Group';
                }

                $result[$key] = $this->aGroup->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collEventAttends) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'eventAttends';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'event_attends';
                        break;
                    default:
                        $key = 'EventAttends';
                }

                $result[$key] = $this->collEventAttends->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collKioskAssignments) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'kioskAssignments';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'kioskassginment_kasms';
                        break;
                    default:
                        $key = 'KioskAssignments';
                }

                $result[$key] = $this->collKioskAssignments->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\ChurchCRM\Event
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = EventTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\ChurchCRM\Event
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setType($value);
                break;
            case 2:
                $this->setTitle($value);
                break;
            case 3:
                $this->setDesc($value);
                break;
            case 4:
                $this->setText($value);
                break;
            case 5:
                $this->setStart($value);
                break;
            case 6:
                $this->setEnd($value);
                break;
            case 7:
                $this->setInActive($value);
                break;
            case 8:
                $this->setTypeName($value);
                break;
            case 9:
                $this->setGroupId($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = EventTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setType($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setTitle($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setDesc($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setText($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setStart($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setEnd($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setInActive($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setTypeName($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setGroupId($arr[$keys[9]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     * @param string $keyType The type of keys the array uses.
     *
     * @return $this|\ChurchCRM\Event The current object, for fluid interface
     */
    public function importFrom($parser, $data, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), $keyType);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(EventTableMap::DATABASE_NAME);

        if ($this->isColumnModified(EventTableMap::COL_EVENT_ID)) {
            $criteria->add(EventTableMap::COL_EVENT_ID, $this->event_id);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TYPE)) {
            $criteria->add(EventTableMap::COL_EVENT_TYPE, $this->event_type);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TITLE)) {
            $criteria->add(EventTableMap::COL_EVENT_TITLE, $this->event_title);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_DESC)) {
            $criteria->add(EventTableMap::COL_EVENT_DESC, $this->event_desc);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TEXT)) {
            $criteria->add(EventTableMap::COL_EVENT_TEXT, $this->event_text);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_START)) {
            $criteria->add(EventTableMap::COL_EVENT_START, $this->event_start);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_END)) {
            $criteria->add(EventTableMap::COL_EVENT_END, $this->event_end);
        }
        if ($this->isColumnModified(EventTableMap::COL_INACTIVE)) {
            $criteria->add(EventTableMap::COL_INACTIVE, $this->inactive);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_TYPENAME)) {
            $criteria->add(EventTableMap::COL_EVENT_TYPENAME, $this->event_typename);
        }
        if ($this->isColumnModified(EventTableMap::COL_EVENT_GRPID)) {
            $criteria->add(EventTableMap::COL_EVENT_GRPID, $this->event_grpid);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = ChildEventQuery::create();
        $criteria->add(EventTableMap::COL_EVENT_ID, $this->event_id);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getId();

        $validPrimaryKeyFKs = 0;
        $primaryKeyFKs = [];

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (event_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \ChurchCRM\Event (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setType($this->getType());
        $copyObj->setTitle($this->getTitle());
        $copyObj->setDesc($this->getDesc());
        $copyObj->setText($this->getText());
        $copyObj->setStart($this->getStart());
        $copyObj->setEnd($this->getEnd());
        $copyObj->setInActive($this->getInActive());
        $copyObj->setTypeName($this->getTypeName());
        $copyObj->setGroupId($this->getGroupId());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getEventAttends() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addEventAttend($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getKioskAssignments() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addKioskAssignment($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \ChurchCRM\Event Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildGroup object.
     *
     * @param  ChildGroup $v
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     * @throws PropelException
     */
    public function setGroup(ChildGroup $v = null)
    {
        if ($v === null) {
            $this->setGroupId(0);
        } else {
            $this->setGroupId($v->getId());
        }

        $this->aGroup = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildGroup object, it will not be re-added.
        if ($v !== null) {
            $v->addEvent($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildGroup object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildGroup The associated ChildGroup object.
     * @throws PropelException
     */
    public function getGroup(ConnectionInterface $con = null)
    {
        if ($this->aGroup === null && ($this->event_grpid !== null)) {
            $this->aGroup = ChildGroupQuery::create()->findPk($this->event_grpid, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aGroup->addEvents($this);
             */
        }

        return $this->aGroup;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('EventAttend' == $relationName) {
            $this->initEventAttends();
            return;
        }
        if ('KioskAssignment' == $relationName) {
            $this->initKioskAssignments();
            return;
        }
    }

    /**
     * Clears out the collEventAttends collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addEventAttends()
     */
    public function clearEventAttends()
    {
        $this->collEventAttends = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collEventAttends collection loaded partially.
     */
    public function resetPartialEventAttends($v = true)
    {
        $this->collEventAttendsPartial = $v;
    }

    /**
     * Initializes the collEventAttends collection.
     *
     * By default this just sets the collEventAttends collection to an empty array (like clearcollEventAttends());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initEventAttends($overrideExisting = true)
    {
        if (null !== $this->collEventAttends && !$overrideExisting) {
            return;
        }

        $collectionClassName = EventAttendTableMap::getTableMap()->getCollectionClassName();

        $this->collEventAttends = new $collectionClassName;
        $this->collEventAttends->setModel('\ChurchCRM\EventAttend');
    }

    /**
     * Gets an array of ChildEventAttend objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildEvent is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildEventAttend[] List of ChildEventAttend objects
     * @throws PropelException
     */
    public function getEventAttends(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collEventAttendsPartial && !$this->isNew();
        if (null === $this->collEventAttends || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collEventAttends) {
                // return empty collection
                $this->initEventAttends();
            } else {
                $collEventAttends = ChildEventAttendQuery::create(null, $criteria)
                    ->filterByEvent($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collEventAttendsPartial && count($collEventAttends)) {
                        $this->initEventAttends(false);

                        foreach ($collEventAttends as $obj) {
                            if (false == $this->collEventAttends->contains($obj)) {
                                $this->collEventAttends->append($obj);
                            }
                        }

                        $this->collEventAttendsPartial = true;
                    }

                    return $collEventAttends;
                }

                if ($partial && $this->collEventAttends) {
                    foreach ($this->collEventAttends as $obj) {
                        if ($obj->isNew()) {
                            $collEventAttends[] = $obj;
                        }
                    }
                }

                $this->collEventAttends = $collEventAttends;
                $this->collEventAttendsPartial = false;
            }
        }

        return $this->collEventAttends;
    }

    /**
     * Sets a collection of ChildEventAttend objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $eventAttends A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildEvent The current object (for fluent API support)
     */
    public function setEventAttends(Collection $eventAttends, ConnectionInterface $con = null)
    {
        /** @var ChildEventAttend[] $eventAttendsToDelete */
        $eventAttendsToDelete = $this->getEventAttends(new Criteria(), $con)->diff($eventAttends);


        $this->eventAttendsScheduledForDeletion = $eventAttendsToDelete;

        foreach ($eventAttendsToDelete as $eventAttendRemoved) {
            $eventAttendRemoved->setEvent(null);
        }

        $this->collEventAttends = null;
        foreach ($eventAttends as $eventAttend) {
            $this->addEventAttend($eventAttend);
        }

        $this->collEventAttends = $eventAttends;
        $this->collEventAttendsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related EventAttend objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related EventAttend objects.
     * @throws PropelException
     */
    public function countEventAttends(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collEventAttendsPartial && !$this->isNew();
        if (null === $this->collEventAttends || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collEventAttends) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getEventAttends());
            }

            $query = ChildEventAttendQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByEvent($this)
                ->count($con);
        }

        return count($this->collEventAttends);
    }

    /**
     * Method called to associate a ChildEventAttend object to this object
     * through the ChildEventAttend foreign key attribute.
     *
     * @param  ChildEventAttend $l ChildEventAttend
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function addEventAttend(ChildEventAttend $l)
    {
        if ($this->collEventAttends === null) {
            $this->initEventAttends();
            $this->collEventAttendsPartial = true;
        }

        if (!$this->collEventAttends->contains($l)) {
            $this->doAddEventAttend($l);

            if ($this->eventAttendsScheduledForDeletion and $this->eventAttendsScheduledForDeletion->contains($l)) {
                $this->eventAttendsScheduledForDeletion->remove($this->eventAttendsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildEventAttend $eventAttend The ChildEventAttend object to add.
     */
    protected function doAddEventAttend(ChildEventAttend $eventAttend)
    {
        $this->collEventAttends[]= $eventAttend;
        $eventAttend->setEvent($this);
    }

    /**
     * @param  ChildEventAttend $eventAttend The ChildEventAttend object to remove.
     * @return $this|ChildEvent The current object (for fluent API support)
     */
    public function removeEventAttend(ChildEventAttend $eventAttend)
    {
        if ($this->getEventAttends()->contains($eventAttend)) {
            $pos = $this->collEventAttends->search($eventAttend);
            $this->collEventAttends->remove($pos);
            if (null === $this->eventAttendsScheduledForDeletion) {
                $this->eventAttendsScheduledForDeletion = clone $this->collEventAttends;
                $this->eventAttendsScheduledForDeletion->clear();
            }
            $this->eventAttendsScheduledForDeletion[]= clone $eventAttend;
            $eventAttend->setEvent(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Event is new, it will return
     * an empty collection; or if this Event has previously
     * been saved, it will retrieve related EventAttends from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Event.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildEventAttend[] List of ChildEventAttend objects
     */
    public function getEventAttendsJoinPerson(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildEventAttendQuery::create(null, $criteria);
        $query->joinWith('Person', $joinBehavior);

        return $this->getEventAttends($query, $con);
    }

    /**
     * Clears out the collKioskAssignments collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addKioskAssignments()
     */
    public function clearKioskAssignments()
    {
        $this->collKioskAssignments = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collKioskAssignments collection loaded partially.
     */
    public function resetPartialKioskAssignments($v = true)
    {
        $this->collKioskAssignmentsPartial = $v;
    }

    /**
     * Initializes the collKioskAssignments collection.
     *
     * By default this just sets the collKioskAssignments collection to an empty array (like clearcollKioskAssignments());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initKioskAssignments($overrideExisting = true)
    {
        if (null !== $this->collKioskAssignments && !$overrideExisting) {
            return;
        }

        $collectionClassName = KioskAssignmentTableMap::getTableMap()->getCollectionClassName();

        $this->collKioskAssignments = new $collectionClassName;
        $this->collKioskAssignments->setModel('\ChurchCRM\KioskAssignment');
    }

    /**
     * Gets an array of ChildKioskAssignment objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildEvent is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildKioskAssignment[] List of ChildKioskAssignment objects
     * @throws PropelException
     */
    public function getKioskAssignments(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collKioskAssignmentsPartial && !$this->isNew();
        if (null === $this->collKioskAssignments || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collKioskAssignments) {
                // return empty collection
                $this->initKioskAssignments();
            } else {
                $collKioskAssignments = ChildKioskAssignmentQuery::create(null, $criteria)
                    ->filterByEvent($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collKioskAssignmentsPartial && count($collKioskAssignments)) {
                        $this->initKioskAssignments(false);

                        foreach ($collKioskAssignments as $obj) {
                            if (false == $this->collKioskAssignments->contains($obj)) {
                                $this->collKioskAssignments->append($obj);
                            }
                        }

                        $this->collKioskAssignmentsPartial = true;
                    }

                    return $collKioskAssignments;
                }

                if ($partial && $this->collKioskAssignments) {
                    foreach ($this->collKioskAssignments as $obj) {
                        if ($obj->isNew()) {
                            $collKioskAssignments[] = $obj;
                        }
                    }
                }

                $this->collKioskAssignments = $collKioskAssignments;
                $this->collKioskAssignmentsPartial = false;
            }
        }

        return $this->collKioskAssignments;
    }

    /**
     * Sets a collection of ChildKioskAssignment objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $kioskAssignments A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildEvent The current object (for fluent API support)
     */
    public function setKioskAssignments(Collection $kioskAssignments, ConnectionInterface $con = null)
    {
        /** @var ChildKioskAssignment[] $kioskAssignmentsToDelete */
        $kioskAssignmentsToDelete = $this->getKioskAssignments(new Criteria(), $con)->diff($kioskAssignments);


        $this->kioskAssignmentsScheduledForDeletion = $kioskAssignmentsToDelete;

        foreach ($kioskAssignmentsToDelete as $kioskAssignmentRemoved) {
            $kioskAssignmentRemoved->setEvent(null);
        }

        $this->collKioskAssignments = null;
        foreach ($kioskAssignments as $kioskAssignment) {
            $this->addKioskAssignment($kioskAssignment);
        }

        $this->collKioskAssignments = $kioskAssignments;
        $this->collKioskAssignmentsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related KioskAssignment objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related KioskAssignment objects.
     * @throws PropelException
     */
    public function countKioskAssignments(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collKioskAssignmentsPartial && !$this->isNew();
        if (null === $this->collKioskAssignments || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collKioskAssignments) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getKioskAssignments());
            }

            $query = ChildKioskAssignmentQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByEvent($this)
                ->count($con);
        }

        return count($this->collKioskAssignments);
    }

    /**
     * Method called to associate a ChildKioskAssignment object to this object
     * through the ChildKioskAssignment foreign key attribute.
     *
     * @param  ChildKioskAssignment $l ChildKioskAssignment
     * @return $this|\ChurchCRM\Event The current object (for fluent API support)
     */
    public function addKioskAssignment(ChildKioskAssignment $l)
    {
        if ($this->collKioskAssignments === null) {
            $this->initKioskAssignments();
            $this->collKioskAssignmentsPartial = true;
        }

        if (!$this->collKioskAssignments->contains($l)) {
            $this->doAddKioskAssignment($l);

            if ($this->kioskAssignmentsScheduledForDeletion and $this->kioskAssignmentsScheduledForDeletion->contains($l)) {
                $this->kioskAssignmentsScheduledForDeletion->remove($this->kioskAssignmentsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildKioskAssignment $kioskAssignment The ChildKioskAssignment object to add.
     */
    protected function doAddKioskAssignment(ChildKioskAssignment $kioskAssignment)
    {
        $this->collKioskAssignments[]= $kioskAssignment;
        $kioskAssignment->setEvent($this);
    }

    /**
     * @param  ChildKioskAssignment $kioskAssignment The ChildKioskAssignment object to remove.
     * @return $this|ChildEvent The current object (for fluent API support)
     */
    public function removeKioskAssignment(ChildKioskAssignment $kioskAssignment)
    {
        if ($this->getKioskAssignments()->contains($kioskAssignment)) {
            $pos = $this->collKioskAssignments->search($kioskAssignment);
            $this->collKioskAssignments->remove($pos);
            if (null === $this->kioskAssignmentsScheduledForDeletion) {
                $this->kioskAssignmentsScheduledForDeletion = clone $this->collKioskAssignments;
                $this->kioskAssignmentsScheduledForDeletion->clear();
            }
            $this->kioskAssignmentsScheduledForDeletion[]= $kioskAssignment;
            $kioskAssignment->setEvent(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Event is new, it will return
     * an empty collection; or if this Event has previously
     * been saved, it will retrieve related KioskAssignments from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Event.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildKioskAssignment[] List of ChildKioskAssignment objects
     */
    public function getKioskAssignmentsJoinKioskDevice(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildKioskAssignmentQuery::create(null, $criteria);
        $query->joinWith('KioskDevice', $joinBehavior);

        return $this->getKioskAssignments($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aGroup) {
            $this->aGroup->removeEvent($this);
        }
        $this->event_id = null;
        $this->event_type = null;
        $this->event_title = null;
        $this->event_desc = null;
        $this->event_text = null;
        $this->event_start = null;
        $this->event_end = null;
        $this->inactive = null;
        $this->event_typename = null;
        $this->event_grpid = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collEventAttends) {
                foreach ($this->collEventAttends as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collKioskAssignments) {
                foreach ($this->collKioskAssignments as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collEventAttends = null;
        $this->collKioskAssignments = null;
        $this->aGroup = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(EventTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postSave')) {
            parent::postSave($con);
        }
    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postInsert')) {
            parent::postInsert($con);
        }
    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postUpdate')) {
            parent::postUpdate($con);
        }
    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preDelete')) {
            return parent::preDelete($con);
        }
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postDelete')) {
            parent::postDelete($con);
        }
    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
