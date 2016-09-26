package main

import "io/ioutil"
import "fmt"
import "regexp"
//import "reflect"
import "net/http"
import "strconv"
import "time"


type Config struct {
	urls []Url
}

type Url struct {
	url1 string 
	url2 string
	pagingfix string
	page_max int

}

var dbh *DBHandler

var useragent string = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0"
const URL_IMMOBILIEN_SEARCH string = "https://www.immobilienscout24.de/Suche/S-T/Wohnung-Miete/Fahrzeitsuche/M_fcnchen/-/113055/2029726/-/1276002059/60/2,00-/-/EURO--800,00?enteredFrom=one_step_search"
var exposelist []string
var GLOBAL_CONFIG Config

func main() {
	load_config()
	dbh = new(DBHandler)
	dbh.Init()
	defer dbh.Close()

	/* Get all the exposes! */
	for _,url := range GLOBAL_CONFIG.urls {
		get_exposes(url)
	}

	/* Store all collected exposes */
	for _,exposeid := range exposelist {
		expose := &Expose{id: exposeid, url: "https://www.immobilienscout24.de/"+exposeid, first_seen: int(time.Now().Unix())}
		dbh.StoreExpose(expose)
	}

	/* Collect information about the exposes */
	listExpose := dbh.GetAllNonCollectedExposes()
	for _,expose := range listExpose {
		collect_expose(&expose)
	}
}

func get_exposes(url Url) {
	for i := 1; i < 50; i++ {
		addr := url.url1 + url.pagingfix + strconv.Itoa(i) + url.url2
		result := get_http_data(addr)
		fmt.Println(addr)
		list := run_regexp_expose(result)
		list = remove_dubbles(list)
		if ! is_already_collected(list) {
			fmt.Println("Not Equal!")
			fmt.Printf("Found a total of %d exposes!\n", len(list))
			fmt.Println(list)
			exposelist = append(exposelist, list...)
		} else {
			fmt.Println("Equal, returning!")
			return
		}
	}
}

/**
 * Extracts information from the expose, by running regex functions. 
 */
func collect_expose(expose *Expose) {
	fmt.Printf("Running expose collection for id: %s (%s)\n", expose.id, expose.url)
	result := get_http_data(expose.url)
	// First check if the expose has been taken down (<h3>Immobilie nicht gefunden.</h3>)

	run_regexp_expose_offline(result, expose)
	if expose.online == TABLE_EXPOSES_YES {
		run_regexp_get_cost(result, expose)
		run_regexp_get_rooms(result, expose)
		run_regexp_get_size(result, expose)
		run_regexp_get_pets(result, expose)		
	}

	expose.collected = TABLE_EXPOSES_YES
	dbh.UpdateExpose(expose)
}


func is_already_collected(list []string) bool {
	for _,expose := range list {
		for _,exp := range exposelist {
			if expose == exp {
				return true
			}
		}
	}
	return false
}


func load_config() {
	url := Url{url1:"https://www.immobilienscout24.de/Suche/S-T",
		url2:"/Wohnung-Miete/Fahrzeitsuche/M_fcnchen/-/113055/2029726/-/1276002059/60/2,00-/-/EURO--800,00?enteredFrom=one_step_search",
		pagingfix:"/P-",
		page_max:0}
	GLOBAL_CONFIG.urls = append(GLOBAL_CONFIG.urls, url)
} 



func run_regexp_get_pets(data []byte, expose *Expose){
	regexpStr := `<dd class="is24qa-haustiere grid-item three-fifths">\s(.+)\s</dd>`
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
	expose.pets = TABLE_EXPOSES_YES
//	fmt.Println(result)
	if len(result) > 0 {
		if len(result[0]) > 0 {
			if result[0][1] == "Nein" {
				expose.pets = TABLE_EXPOSES_NO
			}
		}
	}
	fmt.Println(expose.pets)
}

func run_regexp_expose_offline(data []byte, expose *Expose){
	regexpStr := `<h3>Immobilie nicht gefunden.</h3>`
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
	expose.online = TABLE_EXPOSES_YES
	if len(result) > 0 {
		expose.online = TABLE_EXPOSES_NO
	}
}

func run_regexp_get_rooms(data []byte, expose *Expose){
	regexpStr := `<div class="is24qa-zi is24-value font-semibold">\s(.+)\s</div>`
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
	rooms,_ := strconv.ParseInt(result[0][1], 10, 64)
	fmt.Println("rooms")
	fmt.Println(result)
	expose.rooms = int(rooms)
} 

func run_regexp_get_size(data []byte, expose *Expose){
	regexpStr := `<div class="is24qa-flaeche is24-value font-semibold">\s(.+)\sm²\s</div>`
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
	size,_ := strconv.ParseInt(result[0][1], 10, 64)
	expose.size = int(size)
} 

/**
 * Gets the Warm and cold price of the expose.
 */
func run_regexp_get_cost(data []byte, expose *Expose){
	regexpStr := `<div class="is24qa-kaltmiete is24-value font-semibold">\s(.+)\s€\s</div>`
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
	price_cold,_ := strconv.ParseInt(result[0][1], 10, 64)
	expose.price_cold = int(price_cold)

	regexpStr = `<dd class="is24qa-nebenkosten grid-item three-fifths"> <span class="is24-operator">.+</span>\s(.+)\s€\s</dd>`
	re = regexp.MustCompile(regexpStr)
	result = re.FindAllStringSubmatch(string(data), -1);
	if len(result) > 0 {
		if len(result[0]) > 0{
			price_warm,_ := strconv.ParseInt(result[0][1], 10, 64)
			expose.price_warm = int(price_warm)			
		}
	}
} 


func run_regexp_expose(data []byte) []string{
	regexpStr := `data-go-to-expose-id="(\d+)"`
	var exposes []string
	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);

	for _,value := range result {
		exposes = append(exposes, value[1])
	}
	return exposes
}


func get_http_data(url string) []byte{
    client := &http.Client{}

    req, err := http.NewRequest("GET", url, nil)
    if err != nil {
        fmt.Println(err)
    }

    req.Header.Set("User-Agent", useragent)
	resp, err := client.Do(req)
	htmlData, _ := ioutil.ReadAll(resp.Body)
	return htmlData
}


func remove_dubbles(list []string) []string{
	var resultlist []string
	for _,value := range list {
		if len(resultlist) < 1 {
			resultlist = append(resultlist, value)
		} else {
			exists := false
			for _,result := range resultlist {
				if value == result {
					exists = true
				}
			}
			if !exists {
				resultlist = append(resultlist, value)
			}
		}
	}
	return resultlist
}