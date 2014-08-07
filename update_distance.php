<?php

    $username = 'root';
    $password = 'jeremaroot';
    $conn = new PDO('mysql:host=prod-db.c04plts19dwi.us-east-1.rds.amazonaws.com;dbname=higherme', $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$data = $conn->query('SELECT * FROM applications where id = 20');
	while($row = $data->fetch()) 
	{
        $distance = getDistance($row['user_id'], $row['store_id']);

        $stmt = $conn->prepare('UPDATE applications SET distance = :distance WHERE id = :id');
        $stmt->execute(array(
            ':id'   => $id,
            ':distance' => $distance
        ));
    }

    // Haversine formula
    function Haversine($start, $finish)
    {
        $theta = $start[1] - $finish[1];
        $distance = (sin(deg2rad($start[0])) * sin(deg2rad($finish[0]))) + (cos(deg2rad($start[0])) * cos(deg2rad($finish[0])) * cos(deg2rad($theta)));
        $distance = acos($distance);
        $distance = rad2deg($distance);
        $distance = $distance * 60 * 1.1515;
        return round($distance, 2);
    }

   	function getLatLong($address)
    {

        $address = str_replace(' ', '+', $address);
        $url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $geoloc = curl_exec($ch);

        $json = json_decode($geoloc);
        if ($json->status === 'ZERO_RESULTS')
        {
            return null;
        } else
        {
            return array($json->results[0]->geometry->location->lat, $json->results[0]->geometry->location->lng);
        }
    }

    function getUserAddress($id)
    {
        try
        {
        	$username = 'root';
		    $password = 'jeremaroot';
		    $conn = new PDO('mysql:host=prod-db.c04plts19dwi.us-east-1.rds.amazonaws.com;dbname=higherme', $username, $password);
		    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $stmt = $conn->prepare('SELECT * FROM users_addresses WHERE id = :id');
			$stmt->execute(array('id' => $id));

			$userAddress = $stmt->fetchAll();
 			if ( count($result) ) 
 			{ 
    			foreach($result as $row) 
    			{
					$address = $row['street'] . ' ' . $row['zipcode'];
    			}  
                return $address;
            }
            else
            {
            	return null;
            }
        } catch (Exception $e)
        {
            return null;
        }


    }

    function getStoreAddress($id)
    {
        try
        {
        	$username = 'root';
		    $password = 'jeremaroot';
		    $conn = new PDO('mysql:host=prod-db.c04plts19dwi.us-east-1.rds.amazonaws.com;dbname=higherme', $username, $password);
		    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $stmt = $conn->prepare('SELECT * FROM stores WHERE id = :id');
			$stmt->execute(array('id' => $id));
			$stores = $stmt->fetchAll();
 			if ( count($stores) ) 
 			{ 
    			foreach($stores as $row) 
    			{
					$address = $row['address'] . ' ' . $row['zipcode'];
    			}  
                return $address;
            }
            else
            {
            	return null;
            }
        } catch (Exception $e)
        {
            return null;
        }

    }

    function getDistance($user_address_id, $store_address_id)
    {
        if ($user_address_id != null && $store_address_id != null)
        {
            $start = getLatLong(getUserAddress($user_address_id));
            $finish = getLatLong(getStoreAddress($store_address_id));
            if ($start != null && $finish != null)
            {
                $out = Haversine($start, $finish);
                if ($out)
                {
                    return $out;
                }
                return null;
            }
            return null;

        } else
        {
            return null;
        }
    }