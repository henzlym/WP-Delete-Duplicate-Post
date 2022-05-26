/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice } from '@wordpress/components';
import { 
    useCallback,
    useEffect,
    useState
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

export default function Delete({ currentProgress, isInProgress, posts, setIsInProgress, setCurrentProgress, setPosts }) {
    
    const [queryParams, setQueryParams] = useState( {
        current:0, 
        limit:10, 
        total:posts.is_duplicate.max_num_pages,
        force_delete:false
    } );
    const [query, setQuery] = useState( 0 );
    const [postsDeleted, setTermDeleted] = useState( 0 );
    const [isComplete,setIsComplete] = useState( false );

    const deleteDuplicates = useCallback(async () => {
        await apiFetch({
            path: '/bca/delete-duplicates/v1/delete?' + query.toString(),
        }).then((results) => {

            if (results.error) {
                return;
            }
            
            let { current, limit } = results;
            let currentValue = (current / queryParams.total) * 100;
            let next = current + 1;
            let newNumberOfPostsDeleted = postsDeleted + results.posts.length;
            
            setQueryParams({
                current:next, 
                limit:limit, 
                total:queryParams.total,
                force_delete:false
            });
            setTermDeleted( newNumberOfPostsDeleted );
            setCurrentProgress( currentValue );
        });
    });
    const initDeleteDuplicates = (current, limit, total) => {

        if (total == 0) {
            setIsComplete( true );
            return;
        };
    
        if (current > total) {
            setIsComplete( true );
            return;
        }
    
        if (current < 1) {
            current = 1;
        } else if (current > total) {
            current = total;
        }
    
        let urlSearchParams = new URLSearchParams({
            current,
            limit,
            total,
            force_delete:false
        });
        setQuery( urlSearchParams );
    };

    
    useEffect(
        () => {
            if (query == 0) {
                return;
            }
            deleteDuplicates();
        }, [query]
    )

    useEffect(
        () => {
            setTimeout(() => {
                
            }, 2000);
        }, [isComplete]
    )

    useEffect(
        () => {
            if (!isInProgress||currentProgress==null) {
                return;
            }
            console.log(currentProgress);
            initDeleteDuplicates( queryParams.current, queryParams.limit, queryParams.total );
        }, [isInProgress,currentProgress]
    )

    if (isComplete) {
        return(
            <Notice status={ 'success' } isDismissible={ false }>
			    <p>Process is complete. <strong>{postsDeleted}</strong> duplicated posts have been deleted.</p>
		    </Notice>
        )
    }
    return(
        <div>
            <Button 
                id="start-deletion" 
                variant={(!isInProgress) ? 'primary' : 'secondary' }
                isBusy={isInProgress}
                isSmall
                isDestructive
                disabled={isInProgress}
                onClick={ () => {
                    setIsInProgress( true );
                    setCurrentProgress( 0 );
                }}
            >
                {isInProgress ? 'Deleting Duplicates...' : 'Delete Duplicates' }
            </Button>
            { queryParams.current > 0 && (
                <small>Deleted {postsDeleted} duplicated posts</small>
            )}
            
        </div>
    )
}
