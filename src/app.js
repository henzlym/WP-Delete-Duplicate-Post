
import apiFetch from '@wordpress/api-fetch';
import {
    useEffect,
    useState
} from '@wordpress/element';
import { Header, Main, NewSearch } from "./components";

function Admin(props) {
    
    const [isSearching, setIsSearching] = useState( false );
    const [isInProgress, setIsInProgress] = useState( false );
    const [isLoading, setIsLoading] = useState( true );
    const [currentProgress, setCurrentProgress] = useState( null );
    const [posts, setPosts] = useState( [] );
    
    useEffect(
        () => {

            apiFetch({
                path: '/bca/delete-duplicates/v1/duplicates',
            }).then((results) => {
                if (results.length == 0) {
                    return;
                }
                setIsLoading( false );
                setPosts(results);
            });
        },[]
    )
    // console.log( isSearching );
    // console.log( posts );
    return(
        <div className={`bca-delete-duplicate-posts`}>
            { posts.has_duplicate == false || posts.length == 0 ? (
                <>
                    <Header title="Delete Duplicate Posts" />
                    <NewSearch isSearching={isSearching} setIsSearching={setIsSearching} setPosts={setPosts}/>
                </>
                
            ) : (
                <>
                    <Header title="Delete Duplicate Posts" />
                    <Main 
                        currentProgress={currentProgress}
                        isInProgress={isInProgress} 
                        posts={posts}
                        setCurrentProgress={setCurrentProgress}
                        setIsInProgress={setIsInProgress}
                        setPosts={setPosts}
                    />
                </>
            )}

        </div>
        
    )
}

export default Admin;