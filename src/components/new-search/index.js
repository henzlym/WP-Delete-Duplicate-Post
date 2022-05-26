/**
 * WordPress dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import {
    useCallback,
    useEffect,
    useState
} from '@wordpress/element';
/**
* Internal dependencies
*/
import { ProgressBar } from "../../components";


export default function NewSearch({ isSearching, setIsSearching, setPosts }) {
    const [page, setPage] = useState( 0 );
    const [currentProgress, setCurrentProgress] = useState( 1 );
    const [isTagging, setIsTagging] = useState( false );
    const [postsFound, setPostsFound] = useState( 0 );
    const fetchDuplicates = useCallback(async () => {
        await apiFetch({ path: '/bca/delete-duplicates/v1/search?page=' + page }).then((results) => {
            console.log( results );
            let currentValue = (page / results.max_num_pages) * 100;
            if (results.max_num_pages <= page && results.wp_cache_set == false) {
                setPosts(results);
                setIsSearching(false);
                return;
            }
            

            if (results.wp_cache_set) {
                setCurrentProgress(1);
                setPage( 0 );
                setPostsFound( results.found_posts );
                fetchDuplicates();
                return;
            }

            setCurrentProgress(currentValue);
            setPage( page + 1 );
            
        });
    });

    useEffect(
        () => {
            if (!isSearching) {
                return;
            }
            fetchDuplicates();
        }, [isSearching, page]
    )

    return (
        <Card className='bca-duplicate-posts-new-search'>
            <CardBody>
                <h3>Search your site to find duplicate content.</h3>
                <Button
                    isBusy={isSearching}
                    disabled={isSearching}
                    id="start-search"
                    onClick={() => setIsSearching(true)}
                >
                    { isSearching ? postsFound == 0 ? 'Searching...' : 'Tagging...' : 'Search Site'}
                </Button>
                { isSearching && postsFound == 0 ? <p>Please wait, currently searching your database...</p> : ''}
                { isSearching && postsFound > 0 ? <p>Found <strong>{postsFound}</strong> posts that have been duplicated. Please wait, currently tagging duplicated posts...</p> : ''}
                { isSearching && postsFound > 0 && (
                    <ProgressBar value={currentProgress}/>
                )}
            </CardBody>
        </Card>

    )
}
