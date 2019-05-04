<?php
	
	session_start();

	function getPlan($planName){
		global $db;

		$query = "SELECT * FROM IN_NUTPLAN WHERE NUT_PLAN_NAME = ? ORDER BY Day ASC, Meal_num ASC;";

		$statement = $db->prepare($query);
		$statement->bind_param('s', $planName);
		$statement->execute();
		$data = array();
		$results = $statement->get_result();

		while ($row = $results->fetch_assoc()) {
			array_push($data, $row);
		}
	
		return $data;

	}

	function getGroceries($plan, $planName){
		global $db;

		$query = "SELECT Ingredient_Name, SUM(Ingredient_Amount) as Amount FROM Recipe WHERE Meal_Name in (Select M_Name From IN_NUTPLAN where Nut_Plan_Name = ?) GROUP BY Ingredient_Name;";

		
		$data = array();

		$statement = $db->prepare($query);
		$statement->bind_param('s', $planName);
		$statement->execute();

		$results = $statement->get_result();

		while ($row = $results->fetch_assoc()) {
			array_push($data, $row);
		}

		$newdata = array();

		// for($i = 0; $i < count($data); $i++){
		// 	$newdata[$data[$i]['Ingredient_Name']] = $data[$i]['Amount'];
		// }

		$query2 = "SELECT Ingredient_Name, Unit, Amount*? As Total From ingredient where Ingredient_Name = ?;";

		for($i = 0; $i < count($data); $i++){
			$statement2 = $db->prepare($query2);
			$statement2->bind_param("is", $data[$i]['Amount'], $data[$i]['Ingredient_Name']);
			$statement2->execute();

			$results = $statement2->get_result();

			while ($row = $results->fetch_assoc()) {
				array_push($newdata, $row);
			}
		}

		return $newdata;
	}

	function changeMealPlan($planName){
		global $db;

		$query = "UPDATE User SET NPlan = ? WHERE UserID = ?;";

		$statement = $db->prepare($query);

		$statement->bind_param('ss', $planName, $_SESSION['myusername']);
		$statement->execute();

		$results = $statement->get_result();

		return $results;
	}

	

	function getMeal($mealName){
		global $db;
		$query = "SELECT * FROM Meal WHERE Meal_Name = ?;";

		$statement = $db->prepare($query);

		$statement->bind_param('s', $mealName);

		$statement->execute();

		$results = $statement->get_result();
		
	}

	

	function getIngredient($ingredientName){
		global $db;
		$query = "SELECT * FROM Ingredient WHERE Ingredient_Name = ?;";

		$statement = $db->prepare($query);

		$statement->bind_param('s', $ingredientName);

		$statement->execute();

		$results = $statement->get_result();
	}

	function getRecipe($recipe){
		global $db;
		$query = "SELECT * FROM Recipe WHERE Meal_Name = ? AND Ingredient_Name = ?;";

		$statement = $db->prepare($query);

		$statement->bind_param('s', $mealName);
		#gonna need a for loop to get all the ingredients for a given recipe
		$statement->execute();

		$results = $statement->get_result();
	}

	$db = new mysqli('LOCALHOST', 'root', '', 'FitnessTracker');

	header('Content-Type: application/json');
	switch($_SERVER['REQUEST_METHOD']){
		
		case 'POST':
			$data = json_decode(file_get_contents('php://input'), true);

				
			if(isset($data['planName']) && 0 < strlen($data['planName'])){

				changeMealPlan($data['planName']);

				$info = getPlan($data['planName']);
				$groceries = getGroceries($info, $data['planName']);

				$data = array();

				array_push($data, $info);
				array_push($data, $groceries);

				http_response_code(201);

				echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
				
			}
			
			break;

	}

?>