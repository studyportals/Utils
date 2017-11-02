<?php

/**
 * @file Utils\Vector.php
 * Vector Utility Methods.
 *
 * @author Thijs Putman <thijs@studyportals.eu>
 * @copyright © 2012 StudyPortals B.V., all rights reserved.
 * @version 1.0.0
 */

namespace StudyPortals\Utils;

/**
 * Vector.
 *
 * @package StudyPortals.Framework
 * @subpackage Utils
 */

abstract class Vector{

	/**
	 * Compute similarity between two vectors.
	 *
	 * <p>Uses a vector-space model with cosine similarity to determine the
	 * similarity between two vectors of floating point values.</p>
	 *
	 * <p>The parameter {@link $length_a} and {@link $length_b} can be pre-
	 * computed to further speed-up the calculation. When not provided, they
	 * are calculated on the fly.<br>
	 * Both parameters should be provided as the square-root of the sum of the
	 * squares of all elements of the vector (c.q. the length of the vector).
	 * </p>
	 *
	 * @param array $vector_a
	 * @param array $vector_b
	 * @param float $length_a
	 * @param float $length_b
	 * @return float
	 */

	public static function cosineSimilarity(array $vector_a, array $vector_b,
		$length_a = null, $length_b = null){

		$sum = 0;
		$sum_a_sq = ($length_a === null ? 0.0 : (float) $length_a);
		$sum_b_sq = ($length_b === null ? 0.0 : (float) $length_b);

		foreach($vector_a as $key => $value){

			if(isset($vector_b[$key])) $sum += ($value * $vector_b[$key]);

			if($length_a === null) $sum_a_sq += pow($value, 2);
		}

		if($length_a === null) $sum_a_sq = sqrt($sum_a_sq);

		if($length_b === null){

			foreach($vector_b as $key => $value){

				$sum_b_sq += pow($value, 2);
			}

			$sum_b_sq = sqrt($sum_b_sq);
		}

		$division = $sum_b_sq * $sum_a_sq;

		if($division == 0){

			return 0;
		}
		else{

			$result = $sum / $division;

			return $result;
		}
	}
}