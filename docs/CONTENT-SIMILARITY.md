# Content Similarity & Uniqueness Engine

## Overview

The **Similarity Engine** (`SeoCityPages\Quality\Similarity_Engine`) ensures programmatic city landing pages do not degenerate into copy-paste boilerplate across hundreds or thousands of target locations.

---

## 3-Step Algorithmic Process

### 1. Entity Token Masking & Text Normalization
To prevent false-positive differentiation simply from swapping location names (e.g. replacing "Orlando" with "Tampa"), the engine systematically normalizes the raw content:
- Strips HTML markup and decodes HTML entities.
- Converts all text to lowercase.
- Normalizes punctuation, excessive whitespace, and special characters.
- Masks named entities with universal tokens:
  - Target City Name $\rightarrow$ `__city__`
  - Target State Name $\rightarrow$ `__state__`
  - Target Service Name $\rightarrow$ `__service__`

### 2. 3-Gram Word Shingling
The normalized text string is split into contiguous 3-word shingles (e.g. `["top __service__ provider", "__service__ provider delivering", "provider delivering measurable", ...]`).
- Shingle sets are converted into unique fingerprint hashes stored in `wp_seo_content_fingerprints`.

### 3. Jaccard Set Intersection & Similarity Indexing
Similarity between two pages $A$ and $B$ is measured via the Jaccard similarity coefficient:

$$J(A, B) = \frac{|S_A \cap S_B|}{|S_A \cup S_B|} \times 100\%$$

Where:
- $S_A$ is the set of 3-grams for Page A.
- $S_B$ is the set of 3-grams for Page B.

---

## Partitioned Candidate Comparison (Scale Hardening)

Comparing every page against all $N$ pages in the database is an $O(N^2)$ operation that is unviable at 50,000+ pages. 

To achieve sub-50ms execution:
- The engine partitions candidate queries via indexed lookup:
  - Exact same `service_id` within the same `state_id`
  - Exact same `service_id` nationally
- Scans are capped to the most relevant top 200 candidate fingerprints (`get_candidate_fingerprints()`).
- Fast normalized SHA-256 hash checks instantly catch 100% exact copy-paste duplications without shingle calculation.

---

## Severity Tiers

- **100% Match**: Critical failure (`exact_duplicate_content`, -30.0 penalty).
- **80.0% – 99.9% Match**: High similarity warning (`content_similarity_warning`, -15.0 penalty).
- **< 80.0% Match**: Unique local copy (Passes uniqueness check).
