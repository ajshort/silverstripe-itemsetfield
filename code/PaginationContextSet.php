<?php
/**
 * A workaround for {@link DataObjectSet} to allow setting a custom base URL
 * for pagination links.
 *
 * @package silverstripe-itemsetfield
 * @todo    This functionality should be available in core.
 */
class PaginationContextSet extends DataObjectSet {

	protected $paginationBaseURL;

	/**
	 * @return string
	 */
	public function getPaginationBaseUrl() {
		return $this->paginationBaseURL;
	}

	/**
	 * @param string $url
	 */
	public function setPaginationBaseUrl($url) {
		$this->paginationBaseURL = $url;
	}

	public function PaginationSummary() {
		if ($newBase = $this->getPaginationBaseUrl()) {
			$originalBase = $_SERVER['REQUEST_URI'];
			$_SERVER['REQUEST_URI'] = $newBase;
		}

		$result = parent::PaginationSummary();

		if (isset($originalBase)) {
			$_SERVER['REQUEST_URI'] = $originalBase;
		}

		return $result;
	}

	public function PrevLink() {
		if($this->pageStart - $this->pageLength >= 0) {
			return HTTP::setGetVar(
				$this->paginationGetVar,
				$this->pageStart - $this->pageLength,
				$this->getPaginationBaseUrl());
		}
	}

	public function NextLink() {
		if($this->pageStart + $this->pageLength < $this->totalSize) {
			return HTTP::setGetVar(
				$this->paginationGetVar,
				$this->pageStart + $this->pageLength,
				$this->getPaginationBaseUrl());
		}
	}

}