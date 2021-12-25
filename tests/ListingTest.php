<?php
namespace Tests;
use Core\DB;
use \PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ListingTest extends  TestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = new Client(["base_uri" => $_ENV["TEST_BASE_URL"]]);
    }

    protected function tearDown(): void
    {
        $this->client = "";

        $statement = DB::$con->prepare("TRUNCATE TABLE `listings`");
        $statement->execute();

        $statement = DB::$con->prepare("TRUNCATE TABLE `users`");
        $statement->execute();

    }

    public function create_user(array $data)
    {
        $create_user = $this->client->post('/users', ['form_params' => $data]);
        $user = json_decode($create_user->getBody()->getContents(), true);
        return $user;
    }

    public function login()
    {
        //create user
        $data = 
        [
            "name" => "John Doe",
            "email" => "johndoe@gmail.com",
            "phonenumber" => "0809009090",
            "password" => "password"
        ];

        $this->create_user($data);

        $credentials = ["email" => $data["email"], "password" => $data["password"]];
        $user = $this->client->post('/login', ['form_params' => $credentials]);
        $user = json_decode($user->getBody()->getContents(), true);
        return $user["token"];
    }      

    public function test_that_you_need_to_login_to_create_listing()
    {
        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Osun, Nigeria"
        ];

        $listing = $this->client->post("/listings", ["http_errors" => false, "form_params" => $data]);
        $this->assertEquals("401", $listing->getStatusCode());
    }

    public function test_that_a_wrong_image_url_throws_error()
    {
        $token = $this->login();

        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "test.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Lagos, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);

        $this->assertEquals("400", $listing->getStatusCode());
    } 

    public function test_that_images_upload_to_cloudinary_on_create()
    {
        $token = $this->login();

        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        $created_listing = json_decode($listing->getBody()->getContents(), true);

        $listing_images = $created_listing["images"];

        $listing_images_array = [];
        $listing_images_array =  explode(",", $listing_images);

        $is_cloudinary_url = true;
        foreach($listing_images_array as $listing_image)
        {
            //check if domain on image is from cloudinary
            if(substr($listing_image, 0, 25) == "http://res.cloudinary.com")
                {
                    $is_cloudinary_url = true;
                }else
                {
                    $is_cloudinary_url = false;
                    break;
                }
        }

        $this->assertTrue($is_cloudinary_url);

    }

    public function test_that_listing_returns_correct_data_on_create()
    {
        $token = $this->login();

        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        $created_listing = json_decode($listing->getBody()->getContents(), true);

        $this->assertTrue(array_key_exists("id", $created_listing));
        $this->assertTrue(array_key_exists("name", $created_listing));
        $this->assertTrue(array_key_exists("images", $created_listing));
        $this->assertTrue(array_key_exists("details", $created_listing));
        $this->assertTrue(array_key_exists("location", $created_listing));


        $this->assertEquals($data["name"], ($created_listing["name"]));
        $this->assertEquals($data["description"], ($created_listing["description"]));
        $this->assertEquals($data["details"], ($created_listing["details"]));
        $this->assertEquals($data["location"], ($created_listing["location"]));

        $this->assertEquals("200", $listing->getStatusCode());
    }      

    public function test_that_you_get_an_error_when_creating_listing_with_incomplete_fields()
    {
        $token = $this->login();

        $data = 
        [
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        $this->assertEquals("400", $listing->getStatusCode());
    }

    public function test_that_you_get_json_when_you_get_listing()
    {
        $token = $this->login();

        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        $this->assertJson($listing->getBody()->getContents());
    }

    public function test_that_you_need_to_login_to_update_listing()
    {
        $token = $this->login();

        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Osun, Nigeria"
        ];

        //create listing
        $listing = $this->client->post("/listings",[
            "http_errors" => false,
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);

        //update listing
        $listing_id = json_decode($listing->getBody()->getContents(), true)["id"];
        $updated_listing = $this->client->patch("/listings/".$listing_id,["http_errors" => false, "form_params" => $data]);

        $this->assertEquals("401",$updated_listing->getStatusCode());
    }

    public function test_that_listing_accepts_only_query_data_on_update()
    {
        $token = $this->login();

        //create listing
        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        //update listing
        $updated_data = 
        [
            "name" => "Updated Listing", 
            "description" => "Updated description"
        ];

        $listing_id = json_decode($listing->getBody()->getContents(), true)["id"];
        
        $updated_listing = $this->client->patch("/listings/".$listing_id, [
            "http_errors" => false,
            "form_params" => $updated_data,
            'headers' => ["Authorization" => "Bearer ".$token]]);

        $updated_listing_array = json_decode($updated_listing->getBody()->getContents(), true);

        $this->assertEquals("400", $updated_listing->getStatusCode());

    }

    public function test_that_listing_returns_correct_data_on_update()
    {
        $token = $this->login();

        //create listing
        $data = 
        [
         "name" => "New listing",
         "description" => "It's a really beautiful home",
         "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
         "details" => "It is a 4 story building with 4 windows",
         "location" => "Ogun, Nigeria"
        ];

        $listing = $this->client->post("/listings",[
            "form_params" => $data,
            'headers' => ["Authorization" => "Bearer ".$token]]);
        
        //update listing
        $updated_data = 
        [
            "name" => "Updated Listing", 
            "description" => "Updated description"
        ];

        $listing_id = json_decode($listing->getBody()->getContents(), true)["id"];
        
        $update_listing_url = "/listings/".$listing_id."?name=".$updated_data["name"]."&description=".$updated_data["description"];
        $updated_listing = $this->client->patch($update_listing_url, [
            "form_params" => $updated_data,
            'headers' => ["Authorization" => "Bearer ".$token]]);

        $updated_listing_array = json_decode($updated_listing->getBody()->getContents(), true);

        $this->assertTrue(array_key_exists("id", $updated_listing_array));
        $this->assertTrue(array_key_exists("name", $updated_listing_array));
        $this->assertTrue(array_key_exists("images", $updated_listing_array));
        $this->assertTrue(array_key_exists("details", $updated_listing_array));
        $this->assertTrue(array_key_exists("location", $updated_listing_array));


        $this->assertEquals($updated_data["name"], ($updated_listing_array["name"]));
        $this->assertEquals($updated_data["description"], ($updated_listing_array["description"]));

        $this->assertEquals("200", $updated_listing->getStatusCode());

    }

    public function test_that_you_can_only_update_your_own_listing()
    {

    }

    // public function test_that_you_need_to_delete_to_create_listing()
    // {
    //     $data = 
    //     [
    //      "name" => "New listing",
    //      "description" => "It's a really beautiful home",
    //      "images" => "C:\Users\Mr Fola\Desktop\All\Memes\man jacket.png,C:\Users\Mr Fola\Desktop\All\Memes\child-gun.png,C:\Users\Mr Fola\Desktop\All\Memes\baby-meme.jpg",
    //      "details" => "It is a 4 story building with 4 windows",
    //      "location" => "Osun, Nigeria"
    //     ];

    //     $listing = $this->client->post("/listings", ["http_errors" => false, "form_params" => $data]);
    //     $this->assertEquals("401", $listing->getStatusCode());
    // }
    //test that 
    //test_that_you_can_delete_listing
    //test_that_you_can_only_delete_your_own_listing

}