package main

import "io/ioutil"
import "fmt"
import "regexp"
//import "reflect"
import "net/http"
import "strconv"
//import "encoding/json"


type Config struct {
	urls []Url
}

type Url struct {
	url1 string 
	url2 string
	pagingfix string
	page_max int

}


var useragent string = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0"
const URL_IMMOBILIEN_SEARCH string = "https://www.immobilienscout24.de/Suche/S-T/Wohnung-Miete/Fahrzeitsuche/M_fcnchen/-/113055/2029726/-/1276002059/60/2,00-/-/EURO--800,00?enteredFrom=one_step_search"
var exposelist []string
var GLOBAL_CONFIG Config

func main() {
	load_config()
	/* Get all the exposes! */
	for _,url := range GLOBAL_CONFIG.urls {
		//get_exposes(url)	
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


func run_regexp_expose(data []byte) []string{
	regexpStr := `data-go-to-expose-id="(\d+)"`
	var exposes []string

	re := regexp.MustCompile(regexpStr)
	result := re.FindAllStringSubmatch(string(data), -1);
//	fmt.Printf("%q\n", result)
//	fmt.Printf("\n%s\n", reflect.TypeOf(result))

	for _,value := range result {
//		fmt.Printf("%s is of type: %s \n", value, reflect.TypeOf(value))
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