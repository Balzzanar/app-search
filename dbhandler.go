package main

import (
    _ "github.com/mattn/go-sqlite3"
    "database/sql"
    "fmt"
)

/* -- Constants -- */
const TABLE_EXPOSES = `create table if not exists exposes 
						(id int, name text, price_warm int, price_cold int, last_seen int, 
						first_seen int, care varchar(1), pets varchar(1),
						zipcode varchar(10), city varchar(30), dist_work int,
						kausion int);`

const DB_FILE_NAME = "./list.db"

const TABLE_EXPOSES_NO = "N"
const TABLE_EXPOSES_YES = "Y"

type Expose struct {
	id int
	name string
	price_warm int
	price_cold int
	last_seen int
	first_seen int
	care string
	pets string 
	zipcode string
	city string
	dist_work int
	kausion int
}


type DBHandler struct {
	db *sql.DB
}


/**
 * Opens a connection to the databasefile, creates one if it does not exits
 * 
 * @name Init
 */
func (this *DBHandler) Init() {
	var derr error
	this.db, derr = sql.Open("sqlite3", DB_FILE_NAME)
	if derr != nil {
		fmt.Println(derr)
	}
	this.createNewTable(TABLE_EXPOSES)
}


/**
 * Closes the connection to the databasefile
 * 
 * @name Close
 */
func (this *DBHandler) Close() {
	this.db.Close()
}


/**
 * Stores a wordlist to the databasefile
 * 
 * @name StoreWordlist
 */
func (this *DBHandler) StoreExposes(expose *Expose) {
	if ! this.isWordlistUniqe(wordlist.name) {
		log.Info(fmt.Sprintf("Expose (%d) already exists, updating last seen.", expose.id))
		// expose.last_seen = ## Current Time ##
		this.UpdateExpose(expose)
		return
	}

	tx, err := this.db.Begin()
	if err != nil {
		fmt.Println(err)
	}
	stmt, err := tx.Prepare("insert into expose(id, name, price, first_seen, last_seen, care) values(?, ?, ?, ?, ?, ?)")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	_, err = stmt.Exec(expose.id, expose.name, expose.price, expose.first_seen, expose.last_seen, "")
	if err != nil {
		fmt.Println(err)
	}
	tx.Commit()
}


/**
 * Update a expose in the databasefile
 * 
 * @name UpdateExpose
 */
func (this *DBHandler) UpdateExpose(expose *Expose) {
	tx, err := this.db.Begin()
	if err != nil {
		fmt.Println(err)
	}
	stmt, err := tx.Prepare("update expose set name=?, price=?, last_seen=?, care=? where id=?")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	_, err = stmt.Exec(expose.name, expose.price, expose.last_seen, expose.care, expose.id)
	if err != nil {
		fmt.Println(err)
	}
	tx.Commit()
}



/**
 * Runs a table script on the database file.
 * 
 * @name createNewTable
 */
func (this *DBHandler) createNewTable(tablescript string) {
	_, err := this.db.Exec(tablescript)
	if err != nil {
		log.Error(fmt.Sprintf("%q: %s\n", err, tablescript))
		return
	}
}



/**
 * Checks if the given expose id is uniqe
 * 
 * @name isExposeUniqe
 * @return bool
 */
func (this *DBHandler) isExposeUniqe(id int) bool {
	stmt, err := this.db.Prepare("select count(*) from exposes where id = ?")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	var count int
	err = stmt.QueryRow(id).Scan(&count)
	if err != nil {
		fmt.Println(err)
	}
	if count > 0 {
		return false
	}
	return true
}