package main

import (
    _ "github.com/mattn/go-sqlite3"
    "database/sql"
    "fmt"
    "time"
)

/* -- Constants -- */
const TABLE_EXPOSES = `create table if not exists exposes 
						(id varchar(20), name text, price_warm int, price_cold int, last_seen int, 
						first_seen int, care varchar(1), pets varchar(1),
						zipcode varchar(10), city varchar(30), dist_work int,
						kausion int, url text, collected varchar(1), rooms int, size int,
						online varchar(1));`

const DB_FILE_NAME = "./list.db"

const TABLE_EXPOSES_NO = "N"
const TABLE_EXPOSES_YES = "Y"

type Expose struct {
	id string
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
	url string
	collected string
	rooms int
	size int
	online string
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
func (this *DBHandler) StoreExpose(expose *Expose) {
	if ! this.isExposeUniqe(expose.id) {
		fmt.Printf("Expose (%s) already exists, updating last seen.\n", expose.id)
		exposeObj := this.GetExposeById(expose.id)
		exposeObj.last_seen = int(time.Now().Unix())
		this.UpdateExpose(&exposeObj)
		return
	}

	tx, err := this.db.Begin()
	if err != nil {
		fmt.Println(err)
	}
	stmt, err := tx.Prepare("insert into exposes(id, name, price_warm, price_cold, first_seen, last_seen, care, pets, zipcode, city, dist_work, kausion, url, collected, rooms, size, online) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	_, err = stmt.Exec(expose.id, expose.name, expose.price_warm, expose.price_cold, expose.first_seen, expose.last_seen, TABLE_EXPOSES_YES, expose.pets, expose.zipcode, expose.city, expose.dist_work, expose.kausion, expose.url, TABLE_EXPOSES_NO, expose.rooms, expose.size, expose.online)
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
	stmt, err := tx.Prepare("update exposes set name=?, price_warm=?, price_cold=?, first_seen=?, last_seen=?, care=?, pets=?, zipcode=?, city=?, dist_work=?, kausion=?, url=?, collected=?, rooms=?, size=?, online=? where id=?")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	_, err = stmt.Exec(expose.name, expose.price_warm, expose.price_cold, expose.first_seen, expose.last_seen, expose.care, expose.pets, expose.zipcode, expose.city, expose.dist_work, expose.kausion, expose.url, expose.collected, expose.rooms, expose.size, expose.online, expose.id)
	if err != nil {
		fmt.Println(err)
	}
	tx.Commit()
}


func (this *DBHandler) GetAllNonCollectedExposes() []Expose {
	listexpose := []Expose{}
	rows, err := this.db.Query("select * from exposes where collected = '"+TABLE_EXPOSES_NO+"'")
	if err != nil {
		fmt.Println(err)
	}
	defer rows.Close()
	for rows.Next() {
		var expose Expose
		err = rows.Scan(&expose.id, &expose.name, &expose.price_warm, &expose.price_cold, &expose.first_seen, &expose.last_seen, &expose.care, &expose.pets, &expose.zipcode, &expose.city, &expose.dist_work, &expose.kausion, &expose.url, &expose.collected, &expose.rooms, &expose.size, &expose.online)
		if err != nil {
			fmt.Println(err)
		}
		listexpose = append(listexpose, expose)
	}
	err = rows.Err()
	if err != nil {
		fmt.Println(err)
	}
	return listexpose
}


func (this *DBHandler) GetExposeById(id string) Expose {
	stmt, err := this.db.Prepare("select * from exposes where id = ?")
	if err != nil {
		fmt.Println(err)
	}
	defer stmt.Close()
	var expose Expose
	err = stmt.QueryRow(id).Scan(&expose.id, &expose.name, &expose.price_warm, &expose.price_cold, &expose.first_seen, &expose.last_seen, &expose.care, &expose.pets, &expose.zipcode, &expose.city, &expose.dist_work, &expose.kausion, &expose.url, &expose.collected, &expose.rooms, &expose.size, &expose.online)
	if err != nil {
		fmt.Println(err)
	}
	return expose
}


/**
 * Runs a table script on the database file.
 * 
 * @name createNewTable
 */
func (this *DBHandler) createNewTable(tablescript string) {
	_, err := this.db.Exec(tablescript)
	if err != nil {
		fmt.Printf("%q: %s\n", err, tablescript)
		return
	}
}



/**
 * Checks if the given expose id is uniqe
 * 
 * @name isExposeUniqe
 * @return bool
 */
func (this *DBHandler) isExposeUniqe(id string) bool {
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